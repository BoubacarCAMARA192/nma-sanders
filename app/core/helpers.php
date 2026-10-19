<?php
/**
 * Fonctions utilitaires partagees (echappement, urls, dates, libelles).
 */

declare(strict_types=1);

use Core\Auth;
use Core\Csrf;

/** Echappement HTML systematique (protection XSS). */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Construit une URL interne absolue : url('boulanger/dashboard', ['statut' => 'recue']) */
function url(string $route = '', array $params = []): string
{
    $base = BASE_URL . '/index.php';
    $query = $route === '' ? [] : ['r' => $route];
    $query = array_merge($query, $params);

    return $query === [] ? $base : $base . '?' . http_build_query($query);
}

/** URL d'un asset avec cache-busting sur la date de modification. */
function asset(string $path): string
{
    $file = ROOT_PATH . '/' . ltrim($path, '/');
    $version = is_file($file) ? (string) filemtime($file) : APP_VERSION;

    return BASE_URL . '/' . ltrim($path, '/') . '?v=' . $version;
}

/** Champ cache CSRF pour les formulaires. */
function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

/** Route de deconnexion (avec jeton). */
function logout_url(): string
{
    return url('deconnexion', ['_token' => Csrf::token()]);
}

/** Date lisible en francais : 19 octobre 2026 a 14 h 30 */
function date_fr(?string $datetime, string $format = 'd/m/Y'): string
{
    if ($datetime === null || $datetime === '' || str_starts_with($datetime, '0000')) {
        return '—';
    }

    $ts = strtotime($datetime);

    return $ts === false ? '—' : date($format, $ts);
}

function datetime_fr(?string $datetime): string
{
    return date_fr($datetime, 'd/m/Y à H\hi');
}

/** Libelle lisible d'une operation du journal. */
function action_fr(string $action): string
{
    $libelles = [
        'connexion'                  => 'Connexion réussie',
        'deconnexion'                => 'Déconnexion',
        'connexion_echouee'          => 'Échec de connexion',
        'connexion_bloquee'          => 'Connexion bloquée (tentatives)',
        'connexion_refusee'          => 'Connexion refusée (compte inactif)',
        'acces_refuse'               => 'Accès refusé',
        'acces_refuse_commande'      => 'Accès refusé à une commande',
        'csrf_rejete'                => 'Requête rejetée (CSRF)',
        'route_inconnue'             => 'Page introuvable',
        'commande_creee'             => 'Commande créée',
        'commande_modifiee'          => 'Commande modifiée',
        'commande_annulee'           => 'Commande annulée',
        'commande_validee'           => 'Commande validée',
        'commande_refusee'           => 'Commande refusée',
        'statut_modifie'             => 'Statut de commande modifié',
        'livraison_planifiee'        => 'Livraison planifiée',
        'livraison_etat'             => 'État de livraison modifié',
        'export_csv'                 => 'Export CSV',
        'utilisateur_cree'           => 'Utilisateur créé',
        'utilisateur_modifie'        => 'Utilisateur modifié',
        'utilisateur_activation'     => 'Activation / désactivation de compte',
        'mot_de_passe_reinitialise'  => 'Mot de passe réinitialisé (admin)',
        'mot_de_passe_modifie'       => 'Mot de passe modifié',
        'mot_de_passe_echec'         => 'Échec de changement de mot de passe',
        'profil_modifie'             => 'Profil modifié',
        'reset_demande'              => 'Demande de réinitialisation',
        'reset_effectue'             => 'Réinitialisation effectuée',
        'produit_cree'               => 'Produit créé',
        'produit_modifie'            => 'Produit modifié',
        'produit_activation'         => 'Activation / retrait de produit',
        'stock_ajuste'               => 'Stock ajusté',
        'parametres_modifies'        => 'Paramètres modifiés',
    ];

    return $libelles[$action] ?? ucfirst(str_replace('_', ' ', $action));
}

/** Libelle lisible d'un parametre applicatif. */
function parametre_fr(string $cle): string
{
    $libelles = [
        'delai_minimum_heures'        => 'Délai minimum de livraison',
        'max_tentatives_connexion'    => 'Tentatives de connexion avant blocage',
        'duree_blocage_minutes'       => 'Durée du blocage du compte',
        'session_duree_minutes'       => 'Durée d\'inactivité avant expiration de session',
        'jours_historique_par_defaut' => 'Profondeur de l\'historique par défaut',
    ];

    return $libelles[$cle] ?? ucfirst(str_replace('_', ' ', $cle));
}

/** Libelle francais d'une periode « AAAA-MM » : octobre 2026 */
function periode_fr(string $periode): string
{
    $mois = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
             'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

    $parties = explode('-', $periode);
    if (count($parties) !== 2 || !isset($mois[(int) $parties[1]])) {
        return $periode;
    }

    return $mois[(int) $parties[1]] . ' ' . $parties[0];
}

/** Nombre de jours restants avant une date (signe possible). */
function jours_restants(?string $date): ?int
{
    if ($date === null || $date === '') {
        return null;
    }
    $cible = strtotime($date . ' 00:00:00');
    $aujourdhui = strtotime(date('Y-m-d') . ' 00:00:00');

    return (int) floor(($cible - $aujourdhui) / 86400);
}

function date_livraison_min(): string
{
    return date('Y-m-d', time() + Core\Parametre::entier('delai_minimum_heures', DELAI_MINIMUM_HEURES) * 3600);
}

/**
 * Libelles et couleurs des statuts de commande.
 * @return array{0:string,1:string,2:string} [libelle, classe bootstrap, icone]
 */
function statut_meta(string $statut): array
{
    return match ($statut) {
        'recue'          => ['Reçue',          'secondary', 'inbox'],
        'validee'        => ['Validée',        'info',      'check2-circle'],
        'en_preparation' => ['En préparation', 'warning',   'box-seam'],
        'en_livraison'   => ['En livraison',   'primary',   'truck'],
        'livree'         => ['Livrée',         'success',   'check2-all'],
        'refusee'        => ['Refusée',        'danger',    'x-octagon'],
        'annulee'        => ['Annulée',        'dark',      'slash-circle'],
        default          => ['Inconnu',        'secondary', 'question'],
    };
}

function statut_badge(string $statut): string
{
    [$libelle, $couleur, $icone] = statut_meta($statut);
    $pulse = $statut === 'recue' ? ' data-pulse' : '';

    return '<span class="badge rounded-pill text-bg-' . $couleur . '"' . $pulse . '><i class="bi bi-' . $icone . ' me-1"></i>' . e($libelle) . '</span>';
}

/** Progression d'une commande dans la frese de suivi (0 a 100). */
function statut_progression(string $statut): int
{
    $etapes = ['recue' => 10, 'validee' => 35, 'en_preparation' => 60, 'en_livraison' => 85, 'livree' => 100];

    if (in_array($statut, ['refusee', 'annulee'], true)) {
        return 100;
    }

    return $etapes[$statut] ?? 0;
}

/** Formate une quantite avec son unite : 12 sacs de 50 kg */
function quantite_fr(int $quantite, string $unite): string
{
    $label = str_contains($unite, 'sac') ? 'sac' : $unite;

    return $quantite . ' ' . $label . ($quantite > 1 && $label === 'sac' ? 's' : '');
}

/** Somme des quantites d'une commande. */
function total_sacs(array $lignes): int
{
    $total = 0;
    foreach ($lignes as $ligne) {
        $total += (int) $ligne['quantite'];
    }

    return $total;
}

/** Valeur monetaire XOF : 1 250 000 FCFA */
function fcfa(?float $montant): string
{
    if ($montant === null) {
        return 'Sur devis';
    }

    return number_format($montant, 0, ',', ' ') . ' FCFA';
}

/** Recupere une valeur POST nettoyee. */
function post(string $key, $default = null)
{
    $value = $_POST[$key] ?? $default;

    return is_string($value) ? trim($value) : $value;
}

function get_param(string $key, $default = null)
{
    $value = $_GET[$key] ?? $default;

    return is_string($value) ? trim($value) : $value;
}

/** URL courante de l'espace connecte (pour les liens du menu). */
function is_route(string $route): bool
{
    return (get_param('r') ?? '') === $route;
}

/** Genere une reference lisible : CMD-2026-000123 */
function generer_reference(string $prefixe): string
{
    return $prefixe . '-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
}
