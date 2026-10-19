<?php
/**
 * Espace Boulanger : F04 catalogue, F05 commande, F06 modification/annulation,
 * F07 suivi, F08 historique, F03 profil et mot de passe.
 */

declare(strict_types=1);

namespace Controller;

use Core\Auth;
use Core\Controller;
use Core\Flash;
use Core\Journal;
use Core\Validator;
use Model\Boulanger;
use Model\Commande;
use Model\Produit;
use Model\Utilisateur;

final class BoulangerController extends Controller
{
    private const PANIER = '_panier';

    public function __construct()
    {
        $this->proteger('boulanger');
        $this->titre = 'Mon espace';
    }

    // -----------------------------------------------------------------
    // Tableau de bord
    // -----------------------------------------------------------------
    public function tableauDeBord(): void
    {
        $boulangerId = $this->boulangerId();

        $this->vue('boulanger/tableau-de-bord', [
            'titre'            => 'Tableau de bord',
            'compteurs'        => Commande::compterParStatut($boulangerId),
            'enCours'          => Commande::enCoursDuBoulanger($boulangerId),
            'dernieres'        => Commande::dernieresDuBoulanger($boulangerId, 5),
            'reappro'          => Commande::dernieresLignes($boulangerId, 4),
            'alertes'          => Produit::enAlerte(),
            'nbPanier'         => $this->nombrePanier(),
        ]);
    }

    // -----------------------------------------------------------------
    // F04 : catalogue
    // -----------------------------------------------------------------
    public function catalogue(): void
    {
        $recherche = (string) (get_param('q') ?? '');

        $this->vue('boulanger/catalogue', [
            'titre'     => 'Catalogue',
            'produits'  => Produit::catalogue($recherche),
            'recherche' => $recherche,
            'panier'    => $this->panier(),
        ]);
    }

    // -----------------------------------------------------------------
    // Panier et F05 : nouvelle commande
    // -----------------------------------------------------------------
    public function ajouterPanier(): void
    {
        $produitId = (int) post('produit_id', 0);
        $quantite  = max(1, (int) post('quantite', 1));
        $produit   = Produit::trouver($produitId);

        if ($produit === null || (int) $produit['actif'] !== 1) {
            $this->erreur('Produit indisponible.', 'boulanger/catalogue');
        }

        $panier = $this->panier();
        $panier[$produitId] = min(9999, ($panier[$produitId] ?? 0) + $quantite);
        $this->sauverPanier($panier);

        Flash::succes($quantite . ' × ' . $produit['libelle'] . ' ajouté à votre commande.');
        $this->rediriger((string) (post('retour', 'boulanger/catalogue')));
    }

    public function modifierPanier(): void
    {
        $produitId = (int) post('produit_id', 0);
        $quantite  = (int) post('quantite', 0);
        $panier    = $this->panier();

        if ($quantite <= 0) {
            unset($panier[$produitId]);
        } else {
            $panier[$produitId] = min(9999, $quantite);
        }
        $this->sauverPanier($panier);

        $this->rediriger('boulanger/commande/nouvelle');
    }

    public function viderPanier(): void
    {
        unset($_SESSION[self::PANIER]);
        Flash::info('Panier vidé.');
        $this->rediriger('boulanger/catalogue');
    }

    public function nouvelleCommande(): void
    {
        $lignes = $this->lignesPanier();

        $this->vue('boulanger/nouvelle-commande', [
            'titre'          => 'Nouvelle commande',
            'lignes'         => $lignes,
            'total'          => $this->totalPanier($lignes),
            'dateMinimum'    => date_livraison_min(),
            'delaiHeures'    => Commande::delaiMinimumHeures(),
            'adresse'        => $this->utilisateur['boulanger_adresse'] ?? '',
            'zone'           => $this->utilisateur['zone_livraison'] ?? '',
            'commentaire'    => '',
            'commande'       => null,
        ]);
    }

    public function enregistrerCommande(): void
    {
        $lignes = $this->lignesPanier();
        if ($lignes === []) {
            $this->erreur('Votre panier est vide. Choisissez d\'abord des produits.', 'boulanger/catalogue');
        }

        $validator = $this->validerCommande();
        if ($validator->echoue()) {
            Flash::erreur($validator->premiereErreur());
            $this->rediriger('boulanger/commande/nouvelle');
        }

        $commandeId = Commande::creer($this->boulangerId(), [
            'date_livraison_souhaitee' => (string) post('date_livraison_souhaitee', ''),
            'adresse_livraison'        => (string) post('adresse_livraison', ''),
            'commentaire'              => (string) post('commentaire', '') ?: null,
        ], $lignes);

        unset($_SESSION[self::PANIER]);

        Flash::succes('Votre commande a bien été transmise. Vous pouvez suivre son état en temps réel.');
        $this->rediriger('boulanger/commande', ['id' => $commandeId]);
    }

    // -----------------------------------------------------------------
    // F07 : suivi et F06 : modification / annulation
    // -----------------------------------------------------------------
    public function suiviCommande(): void
    {
        $commande = $this->commandeDuBoulanger();

        $this->vue('boulanger/suivi-commande', [
            'titre'      => 'Commande ' . $commande['reference'],
            'commande'   => $commande,
            'lignes'     => Commande::lignes((int) $commande['id']),
            'historique' => Commande::historiques((int) $commande['id']),
            'dateMinimum'=> date_livraison_min(),
            'modifiable' => in_array($commande['statut'], Commande::STATUTS_MODIFIABLES, true),
            'catalogue'  => Produit::catalogue(),
        ]);
    }

    public function modifierCommande(): void
    {
        $commande = $this->commandeDuBoulanger();

        if (!in_array($commande['statut'], Commande::STATUTS_MODIFIABLES, true)) {
            $this->erreur('Cette commande n\'est plus modifiable.', 'boulanger/commande', ['id' => (int) $commande['id']]);
        }

        $validator = $this->validerCommande();
        if ($validator->echoue()) {
            Flash::erreur($validator->premiereErreur());
            $this->rediriger('boulanger/commande', ['id' => (int) $commande['id']]);
        }

        // Les lignes viennent du formulaire de modification (une quantite par produit).
        $lignes = [];
        foreach ((array) ($_POST['quantites'] ?? []) as $produitId => $quantite) {
            $produitId = (int) $produitId;
            $quantite  = (int) $quantite;
            $produit   = Produit::trouver($produitId);
            if ($quantite > 0 && $produit !== null && (int) $produit['actif'] === 1) {
                $lignes[] = ['produit_id' => $produitId, 'quantite' => min(9999, $quantite)];
            }
        }

        if ($lignes === []) {
            $this->erreur('La commande doit comporter au moins un produit en quantité supérieure ou égale à 1.', 'boulanger/commande', ['id' => (int) $commande['id']]);
        }

        Commande::modifier((int) $commande['id'], [
            'date_livraison_souhaitee' => (string) post('date_livraison_souhaitee', ''),
            'adresse_livraison'        => (string) post('adresse_livraison', ''),
            'commentaire'              => (string) post('commentaire', '') ?: null,
        ], $lignes);

        $this->succes('Commande mise à jour.', 'boulanger/commande', ['id' => (int) $commande['id']]);
    }

    public function annulerCommande(): void
    {
        $commande = $this->commandeDuBoulanger();
        $resultat = Commande::annuler((int) $commande['id'], $this->boulangerId(), (int) Auth::id());

        $resultat['ok']
            ? $this->succes($resultat['message'], 'boulanger/commande', ['id' => (int) $commande['id']])
            : $this->erreur($resultat['message'], 'boulanger/commande', ['id' => (int) $commande['id']]);
    }

    // -----------------------------------------------------------------
    // F08 : historique
    // -----------------------------------------------------------------
    public function historique(): void
    {
        $statut = (string) (get_param('statut') ?? '');
        $q      = (string) (get_param('q') ?? '');

        $this->vue('boulanger/historique', [
            'titre'    => 'Historique de mes commandes',
            'commandes' => Commande::lister([
                'boulanger_id' => $this->boulangerId(),
                'statut'       => $statut,
                'q'            => $q,
            ], 300),
            'statut'   => $statut,
            'q'        => $q,
            'compteurs' => Commande::compterParStatut($this->boulangerId()),
        ]);
    }

    // -----------------------------------------------------------------
    // F03 : profil et mot de passe
    // -----------------------------------------------------------------
    public function profil(): void
    {
        $this->vue('boulanger/profil', [
            'titre'    => 'Mon profil',
            'fiche'    => Boulanger::trouverParUtilisateur((int) Auth::id()),
            'commandes' => Commande::compterParStatut($this->boulangerId()),
        ]);
    }

    public function enregistrerProfil(): void
    {
        $validator = new Validator($_POST);
        $validator->required('nom', 'Le nom du responsable')
                  ->maxLength('nom', 150, 'Le nom')
                  ->required('email', "L'adresse e-mail")
                  ->email('email', "L'adresse e-mail")
                  ->telephone('telephone', 'Le téléphone', true)
                  ->required('nom_boulangerie', 'Le nom de la boulangerie')
                  ->required('adresse', "L'adresse de livraison");

        if ($validator->echoue()) {
            $this->erreur($validator->premiereErreur(), 'boulanger/profil');
        }

        $email = (string) post('email', '');
        if (Utilisateur::emailExiste($email, (int) Auth::id())) {
            $this->erreur('Cette adresse e-mail est déjà utilisée par un autre compte.', 'boulanger/profil');
        }

        Utilisateur::mettreAJour((int) Auth::id(), [
            'nom'                 => (string) post('nom', ''),
            'email'               => $email,
            'telephone'           => (string) post('telephone', '') ?: null,
            'role'                => 'boulanger',
            'double_verification' => 0,
        ]);

        Boulanger::mettreAJour((int) Auth::id(), [
            'nom_boulangerie' => (string) post('nom_boulangerie', ''),
            'adresse'         => (string) post('adresse', ''),
            'ville'           => (string) post('ville', 'Dakar'),
            'zone_livraison'  => (string) post('zone_livraison', '') ?: null,
        ]);

        Journal::enregistrer('profil_modifie', 'utilisateur', (int) Auth::id(), 'Mise à jour du profil boulanger');
        $this->succes('Profil enregistré.', 'boulanger/profil');
    }

    public function changerMotDePasse(): void
    {
        $user = Utilisateur::trouver((int) Auth::id());

        $validator = new Validator($_POST);
        $validator->required('mot_de_passe_actuel', 'Le mot de passe actuel')
                  ->motDePasse('nouveau_mot_de_passe', 'Le nouveau mot de passe')
                  ->identique('nouveau_mot_de_passe', 'nouveau_mot_de_passe_confirmation', 'La confirmation');

        if ($validator->echoue()) {
            $this->erreur($validator->premiereErreur(), 'boulanger/profil');
        }

        if ($user === null || !password_verify((string) post('mot_de_passe_actuel', ''), (string) $user['mot_de_passe'])) {
            Journal::enregistrer('mot_de_passe_echec', 'utilisateur', (int) Auth::id(), 'Ancien mot de passe incorrect');
            $this->erreur('Le mot de passe actuel est incorrect.', 'boulanger/profil');
        }

        Utilisateur::changerMotDePasse((int) Auth::id(), (string) post('nouveau_mot_de_passe', ''));
        Journal::enregistrer('mot_de_passe_modifie', 'utilisateur', (int) Auth::id(), 'Changement de mot de passe');

        $this->succes('Mot de passe modifié.', 'boulanger/profil');
    }

    // -----------------------------------------------------------------
    // Aides internes
    // -----------------------------------------------------------------
    private function commandeDuBoulanger(): array
    {
        $id = (int) (get_param('id') ?? post('id') ?? 0);

        if ($id <= 0 || !Commande::appartientAuBoulanger($id, $this->boulangerId())) {
            Journal::enregistrer('acces_refuse_commande', 'commande', $id, 'Consultation d\'une commande d\'un autre boulanger');
            Flash::erreur('Commande introuvable.');
            $this->rediriger('boulanger/historique');
        }

        $commande = Commande::trouver($id);
        if ($commande === null) {
            $this->rediriger('boulanger/historique');
        }

        return $commande;
    }

    private function validerCommande(): Validator
    {
        $validator = new Validator($_POST);
        $validator->required('date_livraison_souhaitee', 'La date de livraison souhaitée')
                  ->dateFuture('date_livraison_souhaitee', 'La date de livraison souhaitée', date_livraison_min())
                  ->required('adresse_livraison', "L'adresse de livraison")
                  ->maxLength('adresse_livraison', 255, "L'adresse de livraison")
                  ->maxLength('commentaire', 500, 'Le commentaire');

        return $validator;
    }

    /** @return array<int,int> produit_id => quantite */
    private function panier(): array
    {
        $panier = $_SESSION[self::PANIER] ?? [];
        if (!is_array($panier)) {
            return [];
        }

        $propre = [];
        foreach ($panier as $produitId => $quantite) {
            $produitId = (int) $produitId;
            $quantite  = (int) $quantite;
            if ($produitId > 0 && $quantite > 0) {
                $propre[$produitId] = $quantite;
            }
        }

        return $propre;
    }

    private function sauverPanier(array $panier): void
    {
        $_SESSION[self::PANIER] = $panier;
    }

    private function nombrePanier(): int
    {
        return count($this->panier());
    }

    /** Lignes detaillees du panier (avec verification de disponibilite). */
    private function lignesPanier(): array
    {
        $lignes = [];
        foreach ($this->panier() as $produitId => $quantite) {
            $produit = Produit::trouver($produitId);
            if ($produit === null) {
                continue;
            }
            $lignes[] = [
                'produit_id' => $produitId,
                'quantite'   => $quantite,
                'libelle'    => $produit['libelle'],
                'reference'  => $produit['reference'],
                'unite'      => $produit['unite'],
                'prix_unitaire' => $produit['prix_unitaire'],
                'stock_disponible' => (int) $produit['stock_disponible'],
                'actif'      => (int) $produit['actif'],
                'suffisant'  => (int) $produit['stock_disponible'] >= $quantite && (int) $produit['actif'] === 1,
            ];
        }

        return $lignes;
    }

    private function totalPanier(array $lignes): array
    {
        $sacs = 0;
        $montant = 0.0;
        $montantConnu = true;

        foreach ($lignes as $ligne) {
            $sacs += (int) $ligne['quantite'];
            if ($ligne['prix_unitaire'] === null) {
                $montantConnu = false;
            } else {
                $montant += (float) $ligne['prix_unitaire'] * (int) $ligne['quantite'];
            }
        }

        return ['sacs' => $sacs, 'montant' => $montant, 'montant_connu' => $montantConnu];
    }
}
