<?php
/**
 * Routeur : associe une route (parametre r) a un controleur et une methode.
 * Chaque controleur est responsable du controle d'acces par role.
 */

declare(strict_types=1);

namespace Core;

final class Router
{
    /** @var array<string, array{0:string,1:string,2:bool}> route => [controleur, methode, post_uniquement] */
    private array $routes = [];

    public function __construct()
    {
        $this->declarerRoutes();
    }

    public function get(string $route, string $controleur, string $methode): void
    {
        $this->routes['GET ' . $route] = [$controleur, $methode, false];
    }

    public function post(string $route, string $controleur, string $methode): void
    {
        $this->routes['POST ' . $route] = [$controleur, $methode, true];
    }

    /** Toutes les routes de la plateforme. */
    private function declarerRoutes(): void
    {
        // --- Public ------------------------------------------------------
        $this->get('',                        'AuthController', 'accueil');
        $this->get('connexion',               'AuthController', 'formulaireConnexion');
        $this->post('connexion',              'AuthController', 'connexion');
        $this->get('inscription',             'AuthController', 'formulaireInscription');
        $this->post('inscription',            'AuthController', 'inscrire');
        $this->get('double-verification',     'AuthController', 'formulaire2fa');
        $this->post('double-verification',    'AuthController', 'verifier2fa');
        $this->get('deconnexion',             'AuthController', 'deconnexion');
        $this->get('mot-de-passe-oublie',     'AuthController', 'formulaireOubli');
        $this->post('mot-de-passe-oublie',    'AuthController', 'demanderReinitialisation');
        $this->get('reinitialiser',           'AuthController', 'formulaireReinitialisation');
        $this->post('reinitialiser',          'AuthController', 'reinitialiser');

        // --- Boulanger (F03 a F08) ---------------------------------------
        $this->get('boulanger/tableau-de-bord',      'BoulangerController', 'tableauDeBord');
        $this->get('boulanger/catalogue',            'BoulangerController', 'catalogue');
        $this->get('boulanger/commande/nouvelle',    'BoulangerController', 'nouvelleCommande');
        $this->post('boulanger/panier/ajouter',      'BoulangerController', 'ajouterPanier');
        $this->post('boulanger/panier/modifier',     'BoulangerController', 'modifierPanier');
        $this->post('boulanger/panier/vider',        'BoulangerController', 'viderPanier');
        $this->post('boulanger/commande/enregistrer','BoulangerController', 'enregistrerCommande');
        $this->get('boulanger/commande',             'BoulangerController', 'suiviCommande');
        $this->get('boulanger/charge-livraison',     'BoulangerController', 'chargeLivraison');
        $this->post('boulanger/commande/modifier',   'BoulangerController', 'modifierCommande');
        $this->post('boulanger/commande/annuler',    'BoulangerController', 'annulerCommande');
        $this->get('boulanger/historique',           'BoulangerController', 'historique');
        $this->get('boulanger/profil',               'BoulangerController', 'profil');
        $this->post('boulanger/profil',              'BoulangerController', 'enregistrerProfil');
        $this->post('boulanger/profil/mot-de-passe', 'BoulangerController', 'changerMotDePasse');

        // --- Gestionnaire (F09 a F16) ------------------------------------
        $this->get('gestionnaire/tableau-de-bord',        'GestionnaireController', 'tableauDeBord');
        $this->get('gestionnaire/commandes',              'GestionnaireController', 'commandes');
        $this->get('gestionnaire/commande',               'GestionnaireController', 'detailCommande');
        $this->post('gestionnaire/commande/valider',      'GestionnaireController', 'validerCommande');
        $this->post('gestionnaire/commande/refuser',      'GestionnaireController', 'refuserCommande');
        $this->post('gestionnaire/commande/statut',       'GestionnaireController', 'changerStatut');
        $this->get('gestionnaire/livraisons',             'GestionnaireController', 'livraisons');
        $this->post('gestionnaire/livraisons/planifier',  'GestionnaireController', 'planifierLivraison');
        $this->post('gestionnaire/livraisons/etat',       'GestionnaireController', 'majLivraison');
        $this->get('gestionnaire/statistiques',           'GestionnaireController', 'statistiques');
        $this->get('gestionnaire/export',                 'GestionnaireController', 'exportCsv');
        $this->get('gestionnaire/boulangers',             'GestionnaireController', 'boulangers');

        // --- Administrateur (F17, F18) -----------------------------------
        $this->get('administrateur/tableau-de-bord',   'AdminController', 'tableauDeBord');
        $this->get('administrateur/utilisateurs',      'AdminController', 'utilisateurs');
        $this->get('administrateur/utilisateur',       'AdminController', 'detailUtilisateur');
        $this->post('administrateur/utilisateur/creer','AdminController', 'creerUtilisateur');
        $this->post('administrateur/utilisateur/modifier','AdminController', 'modifierUtilisateur');
        $this->post('administrateur/utilisateur/activer','AdminController', 'basculerActivation');
        $this->post('administrateur/utilisateur/mot-de-passe','AdminController', 'reinitialiserMotDePasse');
        $this->get('administrateur/catalogue',         'AdminController', 'catalogue');
        $this->post('administrateur/catalogue/enregistrer','AdminController', 'enregistrerProduit');
        $this->post('administrateur/catalogue/stock',  'AdminController', 'ajusterStock');
        $this->post('administrateur/catalogue/activer','AdminController', 'basculerProduit');
        $this->get('administrateur/statistiques',      'AdminController', 'statistiques');
        $this->get('administrateur/journal',           'AdminController', 'journal');
        $this->get('administrateur/parametres',        'AdminController', 'parametres');
        $this->post('administrateur/parametres',       'AdminController', 'enregistrerParametres');

        // --- Commun ------------------------------------------------------
        $this->get('notifications',                     'NotificationController', 'index');
        $this->post('notifications/tout-lire',          'NotificationController', 'toutLire');
        $this->get('notifications/ouvrir',              'NotificationController', 'ouvrir');
    }

    public function dispatcher(): void
    {
        $route    = (string) (get_param('r') ?? '');
        $methode  = $_SERVER['REQUEST_METHOD'] === 'POST' ? 'POST' : 'GET';
        $definie  = $this->routes[$methode . ' ' . $route] ?? null;

        if ($definie === null) {
            // La route existe peut-etre avec l'autre methode HTTP.
            $autre = $this->routes[($methode === 'POST' ? 'GET' : 'POST') . ' ' . $route] ?? null;
            if ($autre !== null) {
                Flash::erreur('Cette action nécessite une soumission de formulaire.');
                header('Location: ' . url($methode === 'POST' ? '' : $route));
                exit;
            }
            $this->routeInconnue($route);

            return;
        }

        [$controleur, $action] = $definie;

        // Toute requete POST est protegee contre le CSRF.
        if ($methode === 'POST') {
            Csrf::proteger();
        }

        $classe = 'Controller\\' . $controleur;
        if (!class_exists($classe) || !method_exists($classe, $action)) {
            $this->routeInconnue($route);

            return;
        }

        try {
            (new $classe())->{$action}();
        } catch (\Throwable $e) {
            error_log(sprintf('[%s] %s @ %s:%d', $route, $e->getMessage(), $e->getFile(), $e->getLine()));
            if (APP_DEBUG) {
                throw $e;
            }
            View::erreur(500, 'Erreur interne', 'Une erreur est survenue. L\'incident a été enregistré.');
        }
    }

    private function routeInconnue(string $route): void
    {
        Journal::enregistrer('route_inconnue', 'route', null, $route);
        View::erreur(404, 'Page introuvable', 'La page demandée n\'existe pas ou a été déplacée.');
    }
}
