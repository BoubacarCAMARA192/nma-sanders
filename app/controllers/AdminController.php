<?php
/**
 * Espace Administrateur : F17 gestion des utilisateurs, F18 catalogue,
 * statistiques globales, journal des operations et parametres.
 */

declare(strict_types=1);

namespace Controller;

use Core\Auth;
use Core\Controller;
use Core\Database;
use Core\Journal;
use Core\Parametre;
use Core\Validator;
use Model\Boulanger;
use Model\Commande;
use Model\Livraison;
use Model\Produit;
use Model\Utilisateur;

final class AdminController extends Controller
{
    public function __construct()
    {
        $this->proteger('administrateur');
        $this->titre = 'Administration';
    }

    public function tableauDeBord(): void
    {
        $this->vue('admin/tableau-de-bord', [
            'titre'       => 'Tableau de bord administration',
            'users'       => Utilisateur::statistiques(),
            'produits'    => Produit::statistiques(),
            'compteurs'   => Commande::compterParStatut(),
            'stats'       => Commande::statistiques(date('Y-m-01', strtotime('-2 months')), date('Y-m-d')),
            'alertes'     => Produit::enAlerte(),
            'operations'  => Database::fetchAll(
                'SELECT j.*, u.nom AS auteur, u.role AS auteur_role
                 FROM journal_operation j LEFT JOIN utilisateur u ON u.id = j.utilisateur_id
                 ORDER BY j.date_heure DESC, j.id DESC LIMIT 12'
            ),
            'aTraiter'    => Commande::lister(['statut' => 'recue'], 5),
        ]);
    }

    // -----------------------------------------------------------------
    // F17 : gestion des utilisateurs
    // -----------------------------------------------------------------
    public function utilisateurs(): void
    {
        $role      = (string) (get_param('role') ?? '');
        $recherche = (string) (get_param('q') ?? '');

        $this->vue('admin/utilisateurs', [
            'titre'      => 'Gestion des utilisateurs',
            'utilisateurs' => Utilisateur::lister($role !== '' ? $role : null, $recherche !== '' ? $recherche : null),
            'role'       => $role,
            'q'          => $recherche,
            'stats'      => Utilisateur::statistiques(),
            'demandes'   => Database::fetchAll(
                "SELECT id, nom, email, telephone FROM utilisateur
                 WHERE reset_token IS NOT NULL AND reset_expire_le > NOW() ORDER BY maj_le DESC"
            ),
        ]);
    }

    public function detailUtilisateur(): void
    {
        $id   = (int) (get_param('id') ?? 0);
        $user = $id > 0 ? Utilisateur::trouver($id) : null;

        if ($user === null) {
            $this->erreur('Utilisateur introuvable.', 'administrateur/utilisateurs');
        }

        $this->vue('admin/utilisateur', [
            'titre'    => 'Fiche utilisateur',
            'compte'   => $user,
            'fiche'    => $user['boulanger_id'] !== null ? Boulanger::trouverParUtilisateur($id) : null,
            'commandes' => $user['boulanger_id'] !== null ? Commande::dernieresDuBoulanger((int) $user['boulanger_id'], 10) : [],
            'operations' => Database::fetchAll(
                'SELECT * FROM journal_operation WHERE utilisateur_id = :id ORDER BY date_heure DESC LIMIT 20',
                ['id' => $id]
            ),
        ]);
    }

    public function creerUtilisateur(): void
    {
        $validator = $this->validerUtilisateur(null);

        if ($validator->echoue()) {
            $this->erreur($validator->premiereErreur(), 'administrateur/utilisateurs');
        }

        $role = (string) post('role', '');
        $id   = Utilisateur::creer([
            'nom'                 => (string) post('nom', ''),
            'email'               => (string) post('email', ''),
            'telephone'           => (string) post('telephone', '') ?: null,
            'mot_de_passe'        => (string) post('mot_de_passe', ''),
            'role'                => $role,
            'actif'               => (int) post('actif', 1),
            'double_verification' => $role === 'boulanger' ? 0 : (int) post('double_verification', 0),
            'nom_boulangerie'     => (string) post('nom_boulangerie', '') ?: (string) post('nom', ''),
            'adresse'             => (string) post('adresse', '') ?: 'À préciser',
            'ville'               => (string) post('ville', '') ?: 'Dakar',
            'zone_livraison'      => (string) post('zone_livraison', '') ?: null,
        ]);

        Journal::enregistrer('utilisateur_cree', 'utilisateur', $id, 'Création du compte ' . (string) post('email', '') . ' (' . $role . ')');
        $this->succes('Compte créé avec succès.', 'administrateur/utilisateur', ['id' => $id]);
    }

    public function modifierUtilisateur(): void
    {
        $id   = (int) post('id', 0);
        $user = Utilisateur::trouver($id);

        if ($user === null) {
            $this->erreur('Utilisateur introuvable.', 'administrateur/utilisateurs');
        }

        $validator = $this->validerUtilisateur($id);
        if ($validator->echoue()) {
            $this->erreur($validator->premiereErreur(), 'administrateur/utilisateur', ['id' => $id]);
        }

        // Un administrateur ne peut pas se retirer ses propres droits.
        $role = (string) post('role', '');
        if ($id === (int) Auth::id() && $role !== 'administrateur') {
            $this->erreur('Vous ne pouvez pas modifier votre propre rôle.', 'administrateur/utilisateur', ['id' => $id]);
        }

        Utilisateur::mettreAJour($id, [
            'nom'                 => (string) post('nom', ''),
            'email'               => (string) post('email', ''),
            'telephone'           => (string) post('telephone', '') ?: null,
            'role'                => $role,
            'double_verification' => $role === 'boulanger' ? 0 : (int) post('double_verification', 0),
        ]);

        if ($user['boulanger_id'] !== null) {
            Boulanger::mettreAJour($id, [
                'nom_boulangerie' => (string) post('nom_boulangerie', $user['nom_boulangerie'] ?? ''),
                'adresse'         => (string) post('adresse', $user['boulanger_adresse'] ?? ''),
                'ville'           => (string) post('ville', $user['boulanger_ville'] ?? 'Dakar'),
                'zone_livraison'  => (string) post('zone_livraison', $user['zone_livraison'] ?? '') ?: null,
            ]);
        }

        Journal::enregistrer('utilisateur_modifie', 'utilisateur', $id, 'Mise à jour de la fiche ' . (string) post('email', ''));
        $this->succes('Fiche mise à jour.', 'administrateur/utilisateur', ['id' => $id]);
    }

    public function basculerActivation(): void
    {
        $id = (int) post('id', 0);

        if ($id === (int) Auth::id()) {
            $this->erreur('Vous ne pouvez pas désactiver votre propre compte.', 'administrateur/utilisateurs');
        }

        $etat = Utilisateur::basculerActivation($id);
        Journal::enregistrer('utilisateur_activation', 'utilisateur', $id, $etat === 1 ? 'Compte activé' : 'Compte désactivé');

        $this->succes($etat === 1 ? 'Compte activé : le boulanger peut se connecter.' : 'Compte désactivé.', 'administrateur/utilisateurs');
    }

    public function reinitialiserMotDePasse(): void
    {
        $id         = (int) post('id', 0);
        $utilisateur = Utilisateur::trouver($id);

        if ($utilisateur === null) {
            $this->erreur('Utilisateur introuvable.', 'administrateur/utilisateurs');
        }

        $temporaire = 'SC' . random_int(1000, 9999) . 'a';
        Utilisateur::changerMotDePasse($id, $temporaire);

        Journal::enregistrer('mot_de_passe_reinitialise', 'utilisateur', $id, 'Réinitialisation par l\'administrateur');

        \Core\Notifier::a($id, 'Mot de passe réinitialisé', 'Un mot de passe provisoire a été généré par l\'administrateur. Merci de le changer dès votre connexion.', null, 'alerte');

        $this->succes('Mot de passe provisoire pour ' . $utilisateur['email'] . ' : ' . $temporaire . ' (à communiquer puis à changer).', 'administrateur/utilisateur', ['id' => $id]);
    }

    // -----------------------------------------------------------------
    // F18 : gestion du catalogue
    // -----------------------------------------------------------------
    public function catalogue(): void
    {
        $this->vue('admin/catalogue', [
            'titre'    => 'Catalogue des produits',
            'produits' => Produit::tous(),
            'stats'    => Produit::statistiques(),
            'alerte'   => Produit::enAlerte(),
        ]);
    }

    public function enregistrerProduit(): void
    {
        $id = (int) post('id', 0);

        $validator = new Validator($_POST);
        $validator->required('reference', 'La référence')
                  ->maxLength('reference', 50, 'La référence')
                  ->required('libelle', 'Le libellé')
                  ->maxLength('libelle', 150, 'Le libellé')
                  ->required('unite', 'L\'unité de vente')
                  ->entierPositif('stock_disponible', 'Le stock disponible', 0, 1000000)
                  ->entierPositif('seuil_alerte', 'Le seuil d\'alerte', 0, 100000);

        if (post('prix_unitaire') !== null && post('prix_unitaire') !== '' && !is_numeric(str_replace([' ', ','], ['', '.'], (string) post('prix_unitaire')))) {
            $validator->entierPositif('prix_unitaire', 'Le prix unitaire', 1, 100000000);
        }

        $image = trim((string) post('image', ''));
        if ($image !== '' && (
            !preg_match('#^assets/img/produits/[a-zA-Z0-9._-]+$#', $image)
            || !is_file(ROOT_PATH . '/' . $image)
        )) {
            $validator->ajouterErreur('image', 'Le visuel doit être une image présente dans assets/img/produits.');
        }

        if ($validator->echoue()) {
            $this->erreur($validator->premiereErreur(), 'administrateur/catalogue');
        }

        $donnees = [
            'reference'        => (string) post('reference', ''),
            'libelle'          => (string) post('libelle', ''),
            'description'      => (string) post('description', '') ?: null,
            'image'            => $image !== '' ? $image : null,
            'unite'            => (string) post('unite', 'sac de 50 kg'),
            'prix_unitaire'    => str_replace([' ', ','], ['', '.'], (string) post('prix_unitaire', '')),
            'stock_disponible' => (int) post('stock_disponible', 0),
            'seuil_alerte'     => (int) post('seuil_alerte', 20),
            'actif'            => (int) post('actif', 1),
        ];

        $doublon = (int) Database::scalar(
            'SELECT COUNT(*) FROM produit WHERE reference = :r AND id <> :id',
            ['r' => $donnees['reference'], 'id' => $id]
        );
        if ($doublon > 0) {
            $this->erreur('Cette référence produit existe déjà.', 'administrateur/catalogue');
        }

        if ($id > 0) {
            Produit::mettreAJour($id, $donnees);
            Journal::enregistrer('produit_modifie', 'produit', $id, 'Mise à jour : ' . $donnees['libelle']);
            $this->succes('Produit mis à jour.', 'administrateur/catalogue');
        }

        $nouvelId = Produit::creer($donnees);
        Journal::enregistrer('produit_cree', 'produit', $nouvelId, 'Création : ' . $donnees['libelle']);
        $this->succes('Produit ajouté au catalogue.', 'administrateur/catalogue');
    }

    public function ajusterStock(): void
    {
        $id    = (int) post('id', 0);
        $delta = (int) post('delta', 0);
        $produit = Produit::trouver($id);

        if ($produit === null) {
            $this->erreur('Produit introuvable.', 'administrateur/catalogue');
        }
        if ($delta === 0) {
            $this->erreur('Indiquez une quantité à ajouter ou à retirer.', 'administrateur/catalogue');
        }

        $nouveau = Produit::ajusterStock($id, $delta);
        Journal::enregistrer('stock_ajuste', 'produit', $id, ($delta > 0 ? '+' : '') . $delta . ' → ' . $nouveau . ' sac(s)');

        $this->succes('Stock de « ' . $produit['libelle'] . ' » : ' . $nouveau . ' ' . $produit['unite'] . '.', 'administrateur/catalogue');
    }

    public function basculerProduit(): void
    {
        $id  = (int) post('id', 0);
        $etat = Produit::basculerActivation($id);
        Journal::enregistrer('produit_activation', 'produit', $id, $etat === 1 ? 'Produit activé' : 'Produit retiré du catalogue');

        $this->succes($etat === 1 ? 'Produit de nouveau proposé aux boulangers.' : 'Produit retiré du catalogue.', 'administrateur/catalogue');
    }

    // -----------------------------------------------------------------
    // Statistiques globales
    // -----------------------------------------------------------------
    public function statistiques(): void
    {
        $depuis = (string) (get_param('depuis') ?? date('Y-m-01', strtotime('-5 months')));
        $jusqu  = (string) (get_param('jusqu') ?? date('Y-m-d'));

        $this->vue('admin/statistiques', [
            'titre'     => 'Statistiques globales',
            'depuis'    => $depuis,
            'jusqu'     => $jusqu,
            'stats'     => Commande::statistiques($depuis, $jusqu),
            'users'     => Utilisateur::statistiques(),
            'produits'  => Produit::statistiques(),
            'livrStats' => Livraison::statistiques($depuis),
            'boulangers'=> Boulanger::listerAvecActivite(30),
        ]);
    }

    // -----------------------------------------------------------------
    // Journal des operations
    // -----------------------------------------------------------------
    public function journal(): void
    {
        $action = (string) (get_param('action') ?? '');
        $q      = (string) (get_param('q') ?? '');

        $sql    = 'SELECT j.*, u.nom AS auteur, u.role AS auteur_role
                   FROM journal_operation j LEFT JOIN utilisateur u ON u.id = j.utilisateur_id
                   WHERE 1 = 1';
        $params = [];

        if ($action !== '') {
            $sql .= ' AND j.action = :action';
            $params['action'] = $action;
        }
        if ($q !== '') {
            $sql .= ' AND (u.nom LIKE :q OR j.details LIKE :q OR j.entite LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }
        $sql .= ' ORDER BY j.date_heure DESC, j.id DESC LIMIT 400';

        $this->vue('admin/journal', [
            'titre'    => 'Journal des opérations',
            'operations' => Database::fetchAll($sql, $params),
            'actions'  => array_column(Database::fetchAll('SELECT DISTINCT action FROM journal_operation ORDER BY action'), 'action'),
            'action'   => $action,
            'q'        => $q,
            'total'    => (int) Database::scalar('SELECT COUNT(*) FROM journal_operation'),
        ]);
    }

    // -----------------------------------------------------------------
    // Parametres applicatifs
    // -----------------------------------------------------------------
    public function parametres(): void
    {
        $this->vue('admin/parametres', [
            'titre'      => 'Paramètres',
            'parametres' => Parametre::tout(),
        ]);
    }

    public function enregistrerParametres(): void
    {
        $modifiables = [
            'delai_minimum_heures'        => [0, 720, 'Délai minimum de livraison'],
            'capacite_sacs_par_jour'      => [0, 10000, 'Charge maximale de livraison par jour (0 = illimité)'],
            'max_tentatives_connexion'    => [3, 20, 'Tentatives de connexion autorisées'],
            'duree_blocage_minutes'       => [1, 1440, 'Durée du blocage'],
            'session_duree_minutes'       => [5, 1440, 'Durée de session'],
            'jours_historique_par_defaut' => [7, 730, 'Profondeur de l\'historique'],
        ];

        $modifications = [];
        foreach ($modifiables as $cle => [$min, $max, $libelle]) {
            if (!isset($_POST[$cle])) {
                continue;
            }
            $validator = new Validator([$cle => $_POST[$cle]]);
            $validator->entierPositif($cle, $libelle, $min, $max);
            if ($validator->echoue()) {
                $this->erreur($validator->premiereErreur(), 'administrateur/parametres');
            }
            Parametre::enregistrer($cle, (string) (int) $_POST[$cle]);
            $modifications[] = $cle;
        }

        Journal::enregistrer('parametres_modifies', 'parametre', null, implode(', ', $modifications));
        $this->succes('Paramètres enregistrés.', 'administrateur/parametres');
    }

    // -----------------------------------------------------------------
    // Aides internes
    // -----------------------------------------------------------------
    private function validerUtilisateur(?int $id): Validator
    {
        $validator = new Validator($_POST);
        $validator->required('nom', 'Le nom')
                  ->maxLength('nom', 150, 'Le nom')
                  ->required('email', "L'adresse e-mail")
                  ->email('email', "L'adresse e-mail")
                  ->telephone('telephone', 'Le téléphone', true)
                  ->required('role', 'Le rôle')
                  ->dansListe('role', Utilisateur::ROLES, 'Le rôle');

        if ($id === null) {
            $validator->motDePasse('mot_de_passe')
                      ->identique('mot_de_passe', 'mot_de_passe_confirmation', 'La confirmation du mot de passe');
        }

        if (post('role') === 'boulanger') {
            $validator->required('nom_boulangerie', 'Le nom de la boulangerie')
                      ->required('adresse', "L'adresse de livraison");
        }

        if ($validator->echoue()) {
            return $validator;
        }

        if (Utilisateur::emailExiste((string) post('email', ''), $id)) {
            $validator->ajouterErreur('email', 'Cette adresse e-mail est déjà utilisée par un autre compte.');
        }

        return $validator;
    }
}
