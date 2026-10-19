<?php
/**
 * F11 / F12 / F13 : detail d'une commande, validation, refus, statut et livraison.
 * @var array      $commande
 * @var array      $lignes
 * @var array      $historique
 * @var array|null $livraison
 * @var array      $produits
 * @var array      $total
 * @var string|null $prochain
 */
$manquants = [];
foreach ($lignes as $l) {
    if ((int) $l['actif'] !== 1 || (int) $l['stock_disponible'] < (int) $l['quantite']) {
        $manquants[] = $l;
    }
}
$statutsLibelles = [
    'recue'          => 'Reçue',
    'validee'        => 'Validée',
    'en_preparation' => 'En préparation',
    'en_livraison'   => 'En livraison',
    'livree'         => 'Livrée',
    'refusee'        => 'Refusée',
    'annulee'        => 'Annulée',
];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <nav aria-label="fil d'ariane">
            <ol class="breadcrumb small mb-1">
                <li class="breadcrumb-item"><a href="<?= e(url('gestionnaire/commandes')) ?>">Commandes</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($commande['reference']) ?></li>
            </ol>
        </nav>
        <h1 class="h4 fw-bold mb-0">
            <?= e($commande['reference']) ?> <?= statut_badge($commande['statut']) ?>
        </h1>
        <p class="text-muted small mb-0">
            <?= e($commande['nom_boulangerie']) ?> · reçue le <?= e(datetime_fr($commande['date_commande'])) ?>
            · livraison souhaitée le <?= e(date_fr($commande['date_livraison_souhaitee'])) ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-nma" data-imprimer><i class="bi bi-printer me-1"></i>Bon de commande</button>
        <a href="<?= e(url('gestionnaire/commandes')) ?>" class="btn btn-outline-secondary">Retour</a>
    </div>
</div>

<?php if ($manquants !== [] && $commande['statut'] === 'recue'): ?>
    <div class="alert alert-warning d-flex">
        <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>
        <div>
            <strong>Disponibilité insuffisante pour :</strong>
            <?php foreach ($manquants as $m): ?>
                <div class="small">
                    — <?= e($m['libelle']) ?> : demandé <?= (int) $m['quantite'] ?>,
                    disponible <?= (int) $m['stock_disponible'] ?>
                    <?= (int) $m['actif'] !== 1 ? ' (produit retiré du catalogue)' : '' ?>
                </div>
            <?php endforeach; ?>
            <div class="small mt-1">La validation sera refusée par le système : complétez le stock ou refusez la commande avec un motif.</div>
        </div>
    </div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-box-seam text-nma me-2"></i>Lignes de commande</span>
                <span class="nma-pill"><i class="bi bi-bag"></i><?= (int) $total['sacs'] ?> sac(s)</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th class="text-end">Demandé</th>
                                <th class="text-end d-none d-sm-table-cell">Stock</th>
                                <th class="text-end">Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($lignes as $l): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold text-small"><?= e($l['libelle']) ?></div>
                                    <div class="text-small text-muted"><?= e($l['reference']) ?> · <?= e($l['unite']) ?></div>
                                </td>
                                <td class="text-end nma-num"><?= (int) $l['quantite'] ?></td>
                                <td class="text-end d-none d-sm-table-cell">
                                    <span class="badge text-bg-<?= (int) $l['stock_disponible'] >= (int) $l['quantite'] ? 'success' : 'danger' ?>">
                                        <?= (int) $l['stock_disponible'] ?>
                                    </span>
                                </td>
                                <td class="text-end nma-num text-small">
                                    <?= $l['prix_unitaire'] !== null ? e(fcfa((float) $l['prix_unitaire'] * (int) $l['quantite'])) : '—' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="3" class="text-end">Montant total</th>
                                <th class="text-end nma-num">
                                    <?= $total['montant_connu'] ? e(fcfa((float) $total['montant'])) : 'Sur devis' ?>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php if (!empty($commande['commentaire'])): ?>
                <div class="card-footer bg-white">
                    <div class="nma-stat-label">Commentaire du boulanger</div>
                    <div class="text-small"><?= e($commande['commentaire']) ?></div>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-person-lines-fill text-nma me-2"></i>Coordonnées du client</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="nma-stat-label">Boulangerie</div>
                        <div class="fw-semibold"><?= e($commande['nom_boulangerie']) ?></div>
                    </div>
                    <div class="col-6">
                        <div class="nma-stat-label">Contact</div>
                        <div class="fw-semibold"><?= e($commande['boulanger_nom']) ?></div>
                        <a href="tel:<?= e($commande['boulanger_telephone'] ?? '') ?>" class="text-small">
                            <i class="bi bi-telephone me-1"></i><?= e($commande['boulanger_telephone'] ?? '—') ?>
                        </a>
                    </div>
                    <div class="col-12">
                        <div class="nma-stat-label">Adresse de livraison</div>
                        <div class="fw-semibold"><?= e($commande['adresse_livraison']) ?></div>
                        <?php if (!empty($commande['zone_livraison'])): ?>
                            <div class="text-small text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($commande['zone_livraison']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <!-- F11 : validation / refus -->
        <?php if ($commande['statut'] === 'recue'): ?>
            <div class="card mb-3 border-warning">
                <div class="card-header bg-warning-subtle"><i class="bi bi-clipboard-check me-2"></i>Décision à prendre</div>
                <div class="card-body">
                    <form method="post" action="<?= e(url('gestionnaire/commande/valider')) ?>" class="mb-3"
                          data-confirmer="Valider cette commande ? Le stock sera réservé.">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $commande['id'] ?>">
                        <button type="submit" class="btn btn-success w-100 btn-lg">
                            <i class="bi bi-check2-circle me-2"></i>Valider la commande
                        </button>
                    </form>

                    <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="collapse" data-bs-target="#formRefus">
                        <i class="bi bi-x-circle me-2"></i>Refuser avec un motif
                    </button>

                    <div class="collapse mt-3" id="formRefus">
                        <form method="post" action="<?= e(url('gestionnaire/commande/refuser')) ?>"
                              data-confirmer="Confirmer le refus de cette commande ?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $commande['id'] ?>">
                            <label class="form-label" for="motif_refus">Motif du refus (visible par le boulanger)</label>
                            <textarea class="form-control mb-2" id="motif_refus" name="motif_refus" rows="3" maxlength="255"
                                      required placeholder="Ex. quantité non disponible, adresse hors zone de livraison..."></textarea>
                            <button type="submit" class="btn btn-danger w-100">Confirmer le refus</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- F12 : progression du statut -->
        <?php if ($prochain !== null): ?>
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-arrow-right-circle text-nma me-2"></i>Faire progresser le statut</div>
                <div class="card-body">
                    <p class="text-small text-muted">
                        Statut actuel : <strong><?= e($statutsLibelles[$commande['statut']]) ?></strong>.
                        Étape suivante autorisée : <strong><?= e($statutsLibelles[$prochain]) ?></strong>.
                    </p>
                    <form method="post" action="<?= e(url('gestionnaire/commande/statut')) ?>"
                          data-confirmer="Faire passer la commande au statut « <?= e($statutsLibelles[$prochain]) ?> » ?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $commande['id'] ?>">
                        <input type="hidden" name="statut" value="<?= e($prochain) ?>">
                        <label class="form-label" for="note">Note interne (facultatif)</label>
                        <input type="text" class="form-control mb-2" id="note" name="note" maxlength="255"
                               placeholder="Ex. camion N°2, chargement terminé...">
                        <button type="submit" class="btn btn-nma w-100">
                            <i class="bi bi-arrow-right me-2"></i>Passer en « <?= e($statutsLibelles[$prochain]) ?> »
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- F13 : planification de la livraison -->
        <?php if (in_array($commande['statut'], ['validee', 'en_preparation', 'en_livraison'], true)): ?>
            <div class="card mb-3" id="livraison">
                <div class="card-header"><i class="bi bi-truck text-nma me-2"></i>Livraison</div>
                <div class="card-body">
                    <form method="post" action="<?= e(url('gestionnaire/livraisons/planifier')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="commande_id" value="<?= (int) $commande['id'] ?>">
                        <div class="row g-2">
                            <div class="col-12 col-sm-6">
                                <label class="form-label" for="date_livraison">Date de passage</label>
                                <input type="date" class="form-control" id="date_livraison" name="date_livraison"
                                       value="<?= e($livraison['date_livraison'] ?? $commande['date_livraison_souhaitee']) ?>" required>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="form-label" for="livreur">Livreur</label>
                                <input type="text" class="form-control" id="livreur" name="livreur" maxlength="150"
                                       list="listeLivreurs" required
                                       value="<?= e($livraison['livreur'] ?? '') ?>" placeholder="Nom du livreur">
                                <datalist id="listeLivreurs">
                                    <option value="Livreur NMA 1"></option>
                                    <option value="Livreur NMA 2"></option>
                                </datalist>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="form-label" for="vehicule">Véhicule</label>
                                <input type="text" class="form-control" id="vehicule" name="vehicule" maxlength="100"
                                       value="<?= e($livraison['vehicule'] ?? '') ?>" placeholder="Ex. Camion DK-1234-AB">
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="form-label" for="commentaire_liv">Note</label>
                                <input type="text" class="form-control" id="commentaire_liv" name="commentaire" maxlength="255"
                                       value="<?= e($livraison['commentaire'] ?? '') ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-nma w-100 mt-3">
                            <i class="bi bi-calendar-check me-2"></i><?= $livraison !== null ? 'Mettre à jour la livraison' : 'Planifier la livraison' ?>
                        </button>
                    </form>

                    <?php if ($livraison !== null): ?>
                        <div class="nma-sep"></div>
                        <div class="text-small text-muted mb-2">État de la tournée : <strong><?= e($livraison['etat']) ?></strong></div>
                        <form method="post" action="<?= e(url('gestionnaire/livraisons/etat')) ?>" class="d-flex gap-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="commande_id" value="<?= (int) $commande['id'] ?>">
                            <select class="form-select form-select-sm" name="etat">
                                <?php foreach (['planifiee' => 'Planifiée', 'en_cours' => 'En cours', 'livree' => 'Livrée', 'echouee' => 'Échouée'] as $cle => $lib): ?>
                                    <option value="<?= e($cle) ?>" <?= $livraison['etat'] === $cle ? 'selected' : '' ?>><?= e($lib) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-nma text-nowrap">Mettre à jour</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- F07 : historique / tracabilite -->
        <div class="card">
            <div class="card-header"><i class="bi bi-clock-history text-nma me-2"></i>Historique horodaté</div>
            <div class="card-body">
                <div class="nma-frise">
                    <?php foreach ($historique as $h): ?>
                        <?php [$libelle] = statut_meta($h['statut']); ?>
                        <div class="nma-frise-etape <?= in_array($h['statut'], ['refusee', 'annulee'], true) ? 'danger' : 'fait' ?>">
                            <span class="point"><i class="bi bi-check-lg"></i></span>
                            <div class="titre"><?= e($libelle) ?></div>
                            <div class="meta">
                                <i class="bi bi-clock me-1"></i><?= e(datetime_fr($h['date_heure'])) ?>
                                · <?= e(Core\Journal::auteur($h['auteur'] ?? null, $h['auteur_role'] ?? null)) ?>
                            </div>
                            <?php if (!empty($h['motif'])): ?>
                                <div class="nma-timeline-note mt-1"><?= e($h['motif']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
