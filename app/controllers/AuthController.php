<?php
/**
 * F01 : authentification securisee, gestion des roles, double verification
 * et reinitialisation du mot de passe (F03).
 */

declare(strict_types=1);

namespace Controller;

use Core\Auth;
use Core\Controller;
use Core\Database;
use Core\Flash;
use Core\Journal;
use Core\Validator;
use Model\Utilisateur;

final class AuthController extends Controller
{
    private const MAX_RESET_MINUTES = 60;

    /** Page d'accueil : redirige vers l'espace correspondant au role. */
    public function accueil(): void
    {
        if (Auth::connecte()) {
            $this->rediriger(Auth::routeAccueil());
        }
        $this->rediriger('connexion');
    }

    public function formulaireConnexion(): void
    {
        if (Auth::connecte()) {
            $this->rediriger(Auth::routeAccueil());
        }
        $this->vuePublique('auth/connexion', ['titre' => 'Connexion – ' . APP_NAME]);
    }

    public function connexion(): void
    {
        $email      = (string) post('email', '');
        $motDePasse = (string) post('mot_de_passe', '');

        $validator = new Validator($_POST);
        $validator->required('email', "L'adresse e-mail")
                  ->email('email', "L'adresse e-mail")
                  ->required('mot_de_passe', 'Le mot de passe');

        if ($validator->echoue()) {
            Flash::erreur($validator->premiereErreur());
            $this->vuePublique('auth/connexion', ['titre' => 'Connexion – ' . APP_NAME, 'email' => $email]);
            return;
        }

        $resultat = Auth::connecter($email, $motDePasse);

        if ($resultat['statut'] === 'ok') {
            Flash::succes('Bienvenue ' . $resultat['utilisateur']['nom'] . ' !');
            $this->rediriger(Auth::routeAccueil());
        }

        if ($resultat['statut'] === '2fa') {
            $this->rediriger('double-verification');
        }

        Flash::erreur($resultat['message']);
        $this->vuePublique('auth/connexion', ['titre' => 'Connexion – ' . APP_NAME, 'email' => $email]);
    }

    public function formulaire2fa(): void
    {
        if (!Auth::enAttente2fa()) {
            $this->rediriger('connexion');
        }
        $this->vuePublique('auth/double-verification', [
            'titre'    => 'Vérification en deux étapes',
            'codeDemo' => APP_DEBUG ? ($_SESSION['_2fa_demo_code'] ?? null) : null,
        ]);
    }

    public function verifier2fa(): void
    {
        if (!Auth::enAttente2fa()) {
            $this->rediriger('connexion');
        }

        if (!Auth::verifierCode2fa((string) post('code', ''))) {
            Flash::erreur('Code de vérification incorrect. Merci de réessayer.');
            $this->rediriger('double-verification');
        }

        Flash::succes('Vérification réussie.');
        $this->rediriger(Auth::routeAccueil());
    }

    public function deconnexion(): void
    {
        Auth::deconnecter();
        session_start();
        Flash::info('Vous êtes déconnecté. À bientôt !');
        $this->rediriger('connexion');
    }

    public function formulaireOubli(): void
    {
        $this->vuePublique('auth/mot-de-passe-oublie', ['titre' => 'Mot de passe oublié – ' . APP_NAME]);
    }

    /**
     * Genere un jeton de reinitialisation valable une heure.
     * Aucun serveur SMTP n'etant disponible en local, le lien est remis a
     * l'administrateur qui le transmet au demandeur (journal + notification).
     */
    public function demanderReinitialisation(): void
    {
        $email     = (string) post('email', '');
        $validator = new Validator($_POST);
        $validator->required('email', "L'adresse e-mail")->email('email', "L'adresse e-mail");

        if ($validator->echoue()) {
            Flash::erreur($validator->premiereErreur());
            $this->rediriger('mot-de-passe-oublie');
        }

        $user = Utilisateur::parEmail($email);

        // Reponse identique que le compte existe ou non (anti-enumeration).
        if ($user !== null && (int) $user['actif'] === 1) {
            $token = bin2hex(random_bytes(32));
            Database::run(
                'UPDATE utilisateur SET reset_token = :token, reset_expire_le = DATE_ADD(NOW(), INTERVAL :min MINUTE) WHERE id = :id',
                ['token' => hash('sha256', $token), 'min' => self::MAX_RESET_MINUTES, 'id' => (int) $user['id']]
            );

            \Core\Notifier::gestionnaires(
                'Demande de réinitialisation',
                $user['nom'] . ' (' . $user['email'] . ') demande la réinitialisation de son mot de passe.',
                url('administrateur/utilisateurs'),
                'alerte'
            );
            Journal::enregistrer('reset_demande', 'utilisateur', (int) $user['id'], 'Demande de réinitialisation', (int) $user['id']);

            if (APP_DEBUG) {
                $_SESSION['_reset_demo_link'] = url('reinitialiser', ['token' => $token]);
            }
        }

        Flash::succes('Votre demande a été transmise. NMA Sanders vous contactera pour vous communiquer le lien de réinitialisation.');
        $this->rediriger('mot-de-passe-oublie');
    }

    public function formulaireReinitialisation(): void
    {
        $token = (string) (get_param('token') ?? '');
        $user  = $this->parToken($token);

        if ($user === null) {
            Flash::erreur('Ce lien de réinitialisation est invalide ou expiré.');
            $this->rediriger('connexion');
        }

        $this->vuePublique('auth/reinitialiser', [
            'titre' => 'Nouveau mot de passe',
            'token' => $token,
            'email' => $user['email'],
            'lienDemo' => APP_DEBUG ? ($_SESSION['_reset_demo_link'] ?? null) : null,
        ]);
    }

    public function reinitialiser(): void
    {
        $token = (string) post('token', '');
        $user  = $this->parToken($token);

        if ($user === null) {
            Flash::erreur('Ce lien de réinitialisation est invalide ou expiré.');
            $this->rediriger('connexion');
        }

        $validator = new Validator($_POST);
        $validator->motDePasse('mot_de_passe')
                  ->identique('mot_de_passe', 'mot_de_passe_confirmation', 'La confirmation du mot de passe');

        if ($validator->echoue()) {
            Flash::erreur($validator->premiereErreur());
            $this->rediriger('reinitialiser', ['token' => $token]);
        }

        Utilisateur::changerMotDePasse((int) $user['id'], (string) post('mot_de_passe', ''));
        Journal::enregistrer('reset_effectue', 'utilisateur', (int) $user['id'], 'Mot de passe réinitialisé par l\'utilisateur', (int) $user['id']);

        Flash::succes('Mot de passe modifié. Vous pouvez maintenant vous connecter.');
        $this->rediriger('connexion');
    }

    private function parToken(string $token): ?array
    {
        if ($token === '' || !ctype_xdigit($token) || strlen($token) !== 64) {
            return null;
        }

        return Database::fetch(
            'SELECT * FROM utilisateur
             WHERE reset_token = :token AND reset_expire_le IS NOT NULL AND reset_expire_le > NOW() LIMIT 1',
            ['token' => hash('sha256', $token)]
        );
    }
}
