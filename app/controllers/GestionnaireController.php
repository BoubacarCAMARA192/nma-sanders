<?php
/**
 * Espace Gestionnaire : F10 liste et detail, F11 validation/refus,
 * F12 statuts, F13 livraisons, F15 tableau de bord et statistiques, F16 export CSV.
 */

declare(strict_types=1);

namespace Controller;

use Core\Auth;
use Core\Controller;
use Core\Journal;
use Core\Validator;
use Model\Boulanger;
use Model\Commande;
use Model\Livraison;
use Model\Produit;

final class GestionnaireController extends Controller
{
    public function __construct()
    {
        $this->proteger('gestionnaire', 'administrateur');
        $this->titre = 'Pilotage des commandes';
    }

    public function tableauDeBord(): void
    {
        $compteurs = Commande::compterParStatut();

        $this->vue('gestionnaire/tableau-de-bord', [
            'titre'       => 'Tableau de bord',
            'compteurs'   => $compteurs,
            'aTraiter'    => Commande::lister(['statut' => 'recue'], 8),
            'aPlanifier'  => Livraison::aPlanifier(),
            'livraisons'  => Livraison::planning(date('Y-m-d')),
            'alertes'     => Produit::enAlerte(),
            'stats'       => Commande::statistiques(),
            'livrStats'   => Livraison::statistiques(),
        ]);
    }

    // -----------------------------------------------------------------
    // F10 : liste des commandes
    // -----------------------------------------------------------------
    public function commandes(): void
    {
        $filtres = [
            'statut'     => (string) (get_param('statut') ?? ''),
            'q'          => (string) (get_param('q') ?? ''),
            'depuis'     => (string) (get_param('depuis') ?? ''),
            'jusqu'      => (string) (get_param('jusqu') ?? ''),
        ];

        if ($filtres['statut'] === 'a_traiter') {
            $filtres['statuts'] = ['recue'];
            $filtres['statut']  = '';
        }

        $this->vue('gestionnaire/commandes', [
            'titre'     => 'Commandes',
            'commandes' => Commande::lister($filtres, 300),
            'filtres'   => $filtres,
            'compteurs' => Commande::compterParStatut(),
            'boulangers'=> Boulanger::listerAvecActivite(),
        ]);
    }

    public function detailCommande(): void
    {
        $commande = $this->commandeCourante();
        $lignes   = Commande::lignes((int) $commande['id']);

        $this->vue('gestionnaire/detail-commande', [
            'titre'      => 'Commande ' . $commande['reference'],
            'commande'   => $commande,
            'lignes'     => $lignes,
            'historique' => Commande::historiques((int) $commande['id']),
            'livraison'  => Livraison::trouver((int) $commande['id']),
            'produits'   => Produit::catalogue(),
            'total'      => $this->totalLignes($lignes),
            'prochain'   => Commande::SEQUENCE[$commande['statut']] ?? null,
        ]);
    }

    // -----------------------------------------------------------------
    // F11 : validation / refus
    // -----------------------------------------------------------------
    public function validerCommande(): void
    {
        $commande = $this->commandeCourante();
        $resultat = Commande::valider((int) $commande['id'], (int) Auth::id());

        $resultat['ok']
            ? $this->succes($resultat['message'], 'gestionnaire/commande', ['id' => (int) $commande['id']])
            : $this->erreur($resultat['message'], 'gestionnaire/commande', ['id' => (int) $commande['id']]);
    }

    public function refuserCommande(): void
    {
        $commande = $this->commandeCourante();
        $motif    = (string) post('motif_refus', '');

        $validator = new Validator(['motif_refus' => $motif]);
        $validator->required('motif_refus', 'Le motif du refus')->maxLength('motif_refus', 255, 'Le motif du refus');

        if ($validator->echoue()) {
            $this->erreur($validator->premiereErreur(), 'gestionnaire/commande', ['id' => (int) $commande['id']]);
        }

        $resultat = Commande::refuser((int) $commande['id'], (int) Auth::id(), $motif);

        $resultat['ok']
            ? $this->succes($resultat['message'], 'gestionnaire/commande', ['id' => (int) $commande['id']])
            : $this->erreur($resultat['message'], 'gestionnaire/commande', ['id' => (int) $commande['id']]);
    }

    // -----------------------------------------------------------------
    // F12 : mise a jour du statut
    // -----------------------------------------------------------------
    public function changerStatut(): void
    {
        $commande = $this->commandeCourante();
        $statut   = (string) post('statut', '');
        $note     = (string) post('note', '');

        $resultat = Commande::changerStatut((int) $commande['id'], $statut, (int) Auth::id(), $note ?: null);

        $resultat['ok']
            ? $this->succes($resultat['message'], 'gestionnaire/commande', ['id' => (int) $commande['id']])
            : $this->erreur($resultat['message'], 'gestionnaire/commande', ['id' => (int) $commande['id']]);
    }

    // -----------------------------------------------------------------
    // F13 : planning des livraisons
    // -----------------------------------------------------------------
    public function livraisons(): void
    {
        $date = (string) (get_param('date') ?? '');

        $this->vue('gestionnaire/livraisons', [
            'titre'       => 'Planning des livraisons',
            'date'        => $date !== '' ? $date : date('Y-m-d'),
            'planning'    => Livraison::planning($date !== '' ? $date : date('Y-m-d')),
            'aPlanifier'  => Livraison::aPlanifier(),
            'aVenir'      => Livraison::planning(null, true),
            'stats'       => Livraison::statistiques(),
            'livreurs'    => $this->livreursConnus(),
        ]);
    }

    public function planifierLivraison(): void
    {
        $commandeId = (int) post('commande_id', 0);
        $commande   = Commande::trouver($commandeId);

        if ($commande === null) {
            $this->erreur('Commande introuvable.', 'gestionnaire/livraisons');
        }

        $validator = new Validator($_POST);
        $validator->required('date_livraison', 'La date de livraison')
                  ->required('livreur', 'Le livreur')
                  ->maxLength('livreur', 150, 'Le livreur')
                  ->maxLength('vehicule', 100, 'Le véhicule');

        if ($validator->echoue()) {
            $this->erreur($validator->premiereErreur(), 'gestionnaire/commande', ['id' => $commandeId]);
        }

        $donnees = [
            'date_livraison' => (string) post('date_livraison', ''),
            'livreur'        => (string) post('livreur', ''),
            'vehicule'       => (string) post('vehicule', '') ?: null,
            'commentaire'    => (string) post('commentaire', '') ?: null,
        ];

        if (!Livraison::planifier($commandeId, $donnees)) {
            $this->erreur('La livraison ne peut être planifiée qu\'après validation de la commande.', 'gestionnaire/commande', ['id' => $commandeId]);
        }

        Journal::enregistrer('livraison_planifiee', 'commande', $commandeId, 'Livraison du ' . $donnees['date_livraison'] . ' - ' . $donnees['livreur']);

        // Le passage en « en livraison » est propose si la commande est prete.
        if ($commande['statut'] === 'en_preparation') {
            Commande::changerStatut($commandeId, 'en_livraison', (int) Auth::id(), 'Tournée planifiée : ' . $donnees['livreur']);
            Livraison::changerEtat($commandeId, 'en_cours');
        }

        $this->succes('Livraison planifiée pour le ' . date_fr($donnees['date_livraison']) . '.', 'gestionnaire/commande', ['id' => $commandeId]);
    }

    public function majLivraison(): void
    {
        $commandeId = (int) post('commande_id', 0);
        $etat       = (string) post('etat', '');
        $commentaire = (string) post('commentaire', '') ?: null;

        if (!Livraison::changerEtat($commandeId, $etat, $commentaire)) {
            $this->erreur('État de livraison invalide.', 'gestionnaire/livraisons');
        }

        Journal::enregistrer('livraison_etat', 'commande', $commandeId, 'Nouvel état : ' . $etat);

        if ($etat === 'livree') {
            $commande = Commande::trouver($commandeId);
            if ($commande !== null && $commande['statut'] === 'en_livraison') {
                Commande::changerStatut($commandeId, 'livree', (int) Auth::id(), 'Marchandise réceptionnée par le boulanger');
            }
        }

        $this->succes('Livraison mise à jour.', 'gestionnaire/livraisons');
    }

    // -----------------------------------------------------------------
    // F15 : statistiques
    // -----------------------------------------------------------------
    public function statistiques(): void
    {
        $depuis = (string) (get_param('depuis') ?? date('Y-m-01', strtotime('-2 months')));
        $jusqu  = (string) (get_param('jusqu') ?? date('Y-m-d'));

        $this->vue('gestionnaire/statistiques', [
            'titre'   => 'Statistiques',
            'depuis'  => $depuis,
            'jusqu'   => $jusqu,
            'stats'   => Commande::statistiques($depuis, $jusqu),
            'livrStats' => Livraison::statistiques($depuis),
        ]);
    }

    // -----------------------------------------------------------------
    // F16 : export CSV (reprise dans le CRM et SAP)
    // -----------------------------------------------------------------
    public function exportCsv(): void
    {
        $filtres = [
            'statut' => (string) (get_param('statut') ?? ''),
            'q'      => (string) (get_param('q') ?? ''),
            'depuis' => (string) (get_param('depuis') ?? ''),
            'jusqu'  => (string) (get_param('jusqu') ?? ''),
        ];

        $commandes = Commande::donneesExport($filtres);

        Journal::enregistrer('export_csv', 'commande', null, count($commandes) . ' commande(s) exportée(s)');

        $fichier = 'commandes-nma-sanders-' . date('Ymd-His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fichier . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $sortie = fopen('php://output', 'w');
        fwrite($sortie, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel

        fputcsv($sortie, [
            'Reference', 'Date commande', 'Boulangerie', 'Contact', 'Telephone', 'Ville',
            'Adresse livraison', 'Date livraison souhaitee', 'Date livraison planifiee', 'Livreur',
            'Statut', 'Nombre de lignes', 'Total sacs', 'Mode paiement', 'Commentaire', 'Motif refus',
        ], ';');

        foreach ($commandes as $c) {
            [, $libelle] = statut_meta($c['statut']);
            fputcsv($sortie, [
                $c['reference'],
                $c['date_commande'],
                $c['nom_boulangerie'],
                $c['boulanger_nom'],
                $c['boulanger_telephone'],
                $c['ville'] ?? '',
                $c['adresse_livraison'],
                $c['date_livraison_souhaitee'],
                $c['date_livraison'] ?? '',
                $c['livreur'] ?? '',
                $libelle,
                $c['nb_lignes'],
                $c['total_sacs'],
                mode_paiement_libelle($c['mode_paiement'] ?? null),
                $c['commentaire'] ?? '',
                $c['motif_refus'] ?? '',
            ], ';');
        }

        fclose($sortie);
        exit;
    }

    // -----------------------------------------------------------------
    // Fiche clients (suivi commercial)
    // -----------------------------------------------------------------
    public function boulangers(): void
    {
        $this->vue('gestionnaire/boulangers', [
            'titre'     => 'Mes clients boulangers',
            'boulangers'=> Boulanger::listerAvecActivite(),
        ]);
    }

    // -----------------------------------------------------------------
    // Aides internes
    // -----------------------------------------------------------------
    private function commandeCourante(): array
    {
        $id = (int) (get_param('id') ?? post('id') ?? 0);
        $commande = $id > 0 ? Commande::trouver($id) : null;

        if ($commande === null) {
            $this->erreur('Commande introuvable.', 'gestionnaire/commandes');
        }

        return $commande;
    }

    private function totalLignes(array $lignes): array
    {
        $sacs = 0;
        $montant = 0.0;
        $connu = true;

        foreach ($lignes as $ligne) {
            $sacs += (int) $ligne['quantite'];
            if ($ligne['prix_unitaire'] === null) {
                $connu = false;
            } else {
                $montant += (float) $ligne['prix_unitaire'] * (int) $ligne['quantite'];
            }
        }

        return ['sacs' => $sacs, 'montant' => $montant, 'montant_connu' => $connu];
    }

    private function livreursConnus(): array
    {
        $rows = \Core\Database::fetchAll(
            "SELECT DISTINCT livreur FROM livraison WHERE livreur IS NOT NULL AND livreur <> '' ORDER BY livreur"
        );

        $livreurs = array_column($rows, 'livreur');

        return $livreurs === [] ? ['Livreur NMA 1', 'Livreur NMA 2'] : $livreurs;
    }
}
