<?php
/**
 * Authentification, gestion des roles, limitation des tentatives,
 * expiration de session et double verification (2e facteur).
 */

declare(strict_types=1);

namespace Core;

final class Auth
{
    private const SESSION_USER   = '_user_id';
    private const SESSION_TIME   = '_last_activity';
    private const SESSION_2FA    = '_2fa_user';
    private const SESSION_CODE   = '_2fa_code';

    /** Tente une connexion. Retourne 'ok', '2fa' ou un message d'erreur. */
    public static function connecter(string $email, string $motDePasse): array
    {
        $user = Database::fetch(
            'SELECT * FROM utilisateur WHERE email = :email LIMIT 1',
            ['email' => mb_strtolower($email)]
        );

        // Reponse generique pour ne pas reveler l'existence d'un compte.
        if ($user === null) {
            Journal::enregistrer('connexion_echouee', 'utilisateur', null, 'E-mail inconnu : ' . $email, null);

            return ['statut' => 'erreur', 'message' => 'Identifiants incorrects.'];
        }

        if ($user['bloque_jusqua'] !== null && strtotime((string) $user['bloque_jusqua']) > time()) {
            $minutes = (int) ceil((strtotime((string) $user['bloque_jusqua']) - time()) / 60);
            Journal::enregistrer('connexion_bloquee', 'utilisateur', (int) $user['id'], 'Compte temporairement bloqué', (int) $user['id']);

            return ['statut' => 'erreur', 'message' => "Compte temporairement bloqué. Réessayez dans {$minutes} minute(s)."];
        }

        if (!password_verify($motDePasse, (string) $user['mot_de_passe'])) {
            self::enregistrerEchec($user);
            Journal::enregistrer('connexion_echouee', 'utilisateur', (int) $user['id'], 'Mot de passe incorrect', (int) $user['id']);

            return ['statut' => 'erreur', 'message' => 'Identifiants incorrects.'];
        }

        if ((int) $user['actif'] !== 1) {
            Journal::enregistrer('connexion_refusee', 'utilisateur', (int) $user['id'], 'Compte inactif', (int) $user['id']);

            return ['statut' => 'erreur', 'message' => 'Compte non activé. Contactez NMA Sanders.'];
        }

        // Rehachage transparent si le cout bcrypt a evolue.
        if (password_needs_rehash((string) $user['mot_de_passe'], PASSWORD_BCRYPT)) {
            Database::run('UPDATE utilisateur SET mot_de_passe = :hash WHERE id = :id', [
                'hash' => password_hash($motDePasse, PASSWORD_BCRYPT),
                'id'   => (int) $user['id'],
            ]);
        }

        // Double verification pour gestionnaire / administrateur (F-securite).
        if ((int) $user['double_verification'] === 1) {
            $code = (string) random_int(100000, 999999);
            $_SESSION[self::SESSION_2FA]  = (int) $user['id'];
            $_SESSION[self::SESSION_CODE] = password_hash($code, PASSWORD_BCRYPT);

            // En production : envoi du code par SMS / e-mail (Notifier::sms).
            if (APP_DEBUG) {
                $_SESSION['_2fa_demo_code'] = $code;
            }

            return ['statut' => '2fa', 'message' => 'Un code de vérification à 6 chiffres a été envoyé.'];
        }

        self::ouvrirSession($user);

        return ['statut' => 'ok', 'utilisateur' => $user];
    }

    /** Valide le code de double verification. */
    public static function verifierCode2fa(string $code): bool
    {
        $userId = $_SESSION[self::SESSION_2FA] ?? null;
        $hash   = $_SESSION[self::SESSION_CODE] ?? null;

        if ($userId === null || $hash === null || !password_verify(trim($code), (string) $hash)) {
            return false;
        }

        $user = Database::fetch('SELECT * FROM utilisateur WHERE id = :id LIMIT 1', ['id' => (int) $userId]);
        if ($user === null || (int) $user['actif'] !== 1) {
            return false;
        }

        unset($_SESSION[self::SESSION_2FA], $_SESSION[self::SESSION_CODE], $_SESSION['_2fa_demo_code']);
        self::ouvrirSession($user);

        return true;
    }

    /** Indique qu'une connexion est en attente de double verification. */
    public static function enAttente2fa(): bool
    {
        return isset($_SESSION[self::SESSION_2FA]);
    }

    /** Cree la session applicative apres connexion reussie. */
    private static function ouvrirSession(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION[self::SESSION_USER] = (int) $user['id'];
        $_SESSION[self::SESSION_TIME] = time();

        Database::run(
            'UPDATE utilisateur SET tentatives_connexion = 0, bloque_jusqua = NULL, derniere_connexion = NOW() WHERE id = :id',
            ['id' => (int) $user['id']]
        );
        Journal::enregistrer('connexion', 'utilisateur', (int) $user['id'], 'Connexion réussie', (int) $user['id']);
    }

    private static function enregistrerEchec(array $user): void
    {
        $max      = Parametre::entier('max_tentatives_connexion', MAX_TENTATIVES_CONNEXION);
        $minutes  = Parametre::entier('duree_blocage_minutes', DUREE_BLOCAGE_MINUTES);
        $tentatives = (int) $user['tentatives_connexion'] + 1;

        if ($tentatives >= $max) {
            Database::run(
                'UPDATE utilisateur SET tentatives_connexion = 0, bloque_jusqua = DATE_ADD(NOW(), INTERVAL :min MINUTE) WHERE id = :id',
                ['min' => $minutes, 'id' => (int) $user['id']]
            );

            return;
        }

        Database::run('UPDATE utilisateur SET tentatives_connexion = :t WHERE id = :id', [
            't'  => $tentatives,
            'id' => (int) $user['id'],
        ]);
    }

    public static function deconnecter(): void
    {
        if (self::id() !== null) {
            Journal::enregistrer('deconnexion', 'utilisateur', self::id(), 'Fin de session');
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
    }

    public static function id(): ?int
    {
        return isset($_SESSION[self::SESSION_USER]) ? (int) $_SESSION[self::SESSION_USER] : null;
    }

    public static function connecte(): bool
    {
        return self::id() !== null;
    }

    /** Utilisateur courant (avec infos boulanger le cas echeant), mis en cache pour la requete. */
    public static function utilisateur(): ?array
    {
        static $cache = null;
        static $cacheId = null;

        $id = self::id();
        if ($id === null) {
            return null;
        }
        if ($cacheId === $id && $cache !== null) {
            return $cache;
        }

        $user = Database::fetch(
            "SELECT u.*, b.id AS boulanger_id, b.nom_boulangerie, b.adresse AS boulanger_adresse,
                    b.ville AS boulanger_ville, b.zone_livraison
             FROM utilisateur u
             LEFT JOIN boulanger b ON b.utilisateur_id = u.id
             WHERE u.id = :id LIMIT 1",
            ['id' => $id]
        );

        if ($user === null || (int) $user['actif'] !== 1) {
            self::deconnecter();

            return null;
        }

        unset($user['mot_de_passe'], $user['code_securite'], $user['reset_token']);
        $cache   = $user;
        $cacheId = $id;

        return $cache;
    }

    public static function role(): ?string
    {
        return self::utilisateur()['role'] ?? null;
    }

    public static function est(string ...$roles): bool
    {
        return in_array(self::role(), $roles, true);
    }

    /** Bloque l'acces si l'utilisateur n'a pas l'un des roles autorises. */
    public static function exigerRole(string ...$roles): array
    {
        if (!self::connecte()) {
            Flash::avertir('Veuillez vous connecter pour accéder à cette page.');
            header('Location: ' . url('connexion'));
            exit;
        }

        $user = self::utilisateur();
        if ($user === null || !in_array($user['role'], $roles, true)) {
            Journal::enregistrer('acces_refuse', 'page', null, 'Tentative d\'accès non autorisé : ' . (get_param('r') ?? ''));
            View::erreur(
                403,
                'Accès non autorisé',
                'Votre profil ne permet pas d\'accéder à cette page. Cette tentative a été enregistrée dans le journal des opérations.'
            );
            exit;
        }

        self::verifierExpiration();

        return $user;
    }

    /** Expiration de session apres inactivite (DUREE_SESSION_MINUTES). */
    public static function verifierExpiration(): void
    {
        if (!self::connecte()) {
            return;
        }

        $duree = Parametre::entier('session_duree_minutes', DUREE_SESSION_MINUTES) * 60;
        $derniere = (int) ($_SESSION[self::SESSION_TIME] ?? time());

        if (time() - $derniere > $duree) {
            self::deconnecter();
            session_start();
            Flash::avertir('Votre session a expiré pour inactivité. Merci de vous reconnecter.');
            header('Location: ' . url('connexion'));
            exit;
        }

        $_SESSION[self::SESSION_TIME] = time();
    }

    /** Route d'accueil selon le role connecte. */
    public static function routeAccueil(): string
    {
        return match (self::role()) {
            'boulanger'      => 'boulanger/tableau-de-bord',
            'gestionnaire'   => 'gestionnaire/tableau-de-bord',
            'administrateur' => 'administrateur/tableau-de-bord',
            default          => 'connexion',
        };
    }
}
