<?php
/**
 * F07 : suivi d'une commande par statuts horodates (frise) et
 * F06 : modification / annulation tant que la commande est « recue ».
 *
 * @var array  $commande
 * @var array  $lignes
 * @var array  $historique
 * @var string $dateMinimum
 * @var bool   $modifiable
 * @var array  $catalogue
 */
$etapes = [
    'recue'          => ['Commande reçue',         'Votre commande est enregistrée et attend la validation du gestionnaire.'],
    'validee'        => ['Commande validée',       'La disponibilité du produit est confirmée, le stock est réservé.'],
    'en_preparation' => ['En préparation',         'La marchandise est en cours de préparation dans nos entrepôts.'],
    'en_livraison'   => ['En livraison',           'Votre commande est en route vers votre boulangerie.'],
    'livree'         => ['Livrée',                 'Marchandise réceptionnée. Merci de votre confiance.'],
];

// Derniere trace horodatee pour chaque statut.
$traces = [];
foreach ($historique as $h) {
    $traces[$h['statut']] = $h;
}
$suivie   = array_keys($etapes);
$position = array_search($commande['statut'], $suivie, true);
$interrompu = in_array($commande['statut'], ['refusee', 'annulee'], true);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <nav aria-label="fil d'ariane">
            <ol class="breadcrumb small mb-1">
                <li class="breadcrumb-item"><a href="<?= e(url('boulanger/historique')) ?>">Mes commandes</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($commande['reference']) ?></li>
            </ol>
        </nav>
        <h1 class="h4 fw-bold mb-0">
            Commande <?= e($commande['reference']) ?>
            <?= statut_badge($commande['statut']) ?>
        </h1>
        <p class="text-muted small mb-0">
            Déposée le <?= e(datetime_fr($commande['date_commande'])) ?> ·
            livraison souhaitée le <?= e(date_fr($commande['date_livraison_souhaitee'])) ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-sc" data-imprimer>
            <i class="bi bi-printer me-1"></i>Imprimer
        </button>
        <?php if ($modifiable): ?>
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalModifier">
                <i class="bi bi-pencil-square me-1"></i>Modifier
            </button>
        <?php endif; ?>
    </div>
</div>

<?php if ($interrompu): ?>
    <div class="alert alert-<?= $commande['statut'] === 'refusee' ? 'danger' : 'dark' ?> d-flex">
        <i class="bi bi-<?= $commande['statut'] === 'refusee' ? 'x-octagon-fill' : 'slash-circle-fill' ?> me-2 mt-1"></i>
        <div>
            <strong><?= $commande['statut'] === 'refusee' ? 'Commande refusée' : 'Commande annulée' ?></strong>
            <?php if (!empty($commande['motif_refus'])): ?>
                <div class="mt-1">Motif : <?= e($commande['motif_refus']) ?></div>
            <?php else: ?>
                <div class="mt-1">Cette commande a été annulée. Vous pouvez repasser commande à tout moment.</div>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between text-small fw-semibold mb-1">
                <span>Progression</span>
                <span><?= statut_progression($commande['statut']) ?>%</span>
            </div>
            <div class="progress sc-progress">
                <div class="progress-bar" style="width: <?= statut_progression($commande['statut']) ?>%"></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-diagram-3 text-sc me-2"></i>Suivi étape par étape</div>
            <div class="card-body">
                <?php if ($interrompu): ?>
                    <div class="sc-frise">
                        <?php foreach ($historique as $h): ?>
                            <?php [$libelle] = statut_meta($h['statut']); ?>
                            <div class="sc-frise-etape <?= in_array($h['statut'], ['refusee', 'annulee'], true) ? 'danger' : 'fait' ?>">
                                <span class="point"><i class="bi bi-<?= in_array($h['statut'], ['refusee', 'annulee'], true) ? 'x-lg' : 'check-lg' ?>"></i></span>
                                <div class="titre"><?= e($libelle) ?></div>
                                <div class="meta">
                                    <i class="bi bi-clock me-1"></i><?= e(datetime_fr($h['date_heure'])) ?>
                                    · <?= e(Core\Journal::auteur($h['auteur'] ?? null, $h['auteur_role'] ?? null)) ?>
                                </div>
                                <?php if (!empty($h['motif'])): ?>
                                    <div class="sc-timeline-note mt-1"><?= e($h['motif']) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="sc-frise">
                        <?php foreach ($etapes as $statut => $info):
                            $index = array_search($statut, $suivie, true);
                            $classe = $index < $position ? 'fait' : ($index === $position ? 'actuel' : 'futur');
                            $trace  = $traces[$statut] ?? null;
                            ?>
                            <div class="sc-frise-etape <?= $classe ?>">
                                <span class="point">
                                    <i class="bi bi-<?= $classe === 'fait' ? 'check-lg' : ($classe === 'actuel' ? 'dot' : 'circle') ?>"></i>
                                </span>
                                <div class="titre"><?= e($info[0]) ?></div>
                                <?php if ($trace !== null): ?>
                                    <div class="meta">
                                        <i class="bi bi-clock me-1"></i><?= e(datetime_fr($trace['date_heure'])) ?>
                                        · <?= e(Core\Journal::auteur($trace['auteur'] ?? null, $trace['auteur_role'] ?? null)) ?>
                                    </div>
                                    <?php if (!empty($trace['motif'])): ?>
                                        <div class="sc-timeline-note mt-1"><?= e($trace['motif']) ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="meta"><?= e($info[1]) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($commande['date_livraison']) || !empty($commande['livreur'])): ?>
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-truck text-sc me-2"></i>Livraison planifiée</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="sc-stat-label">Date de passage</div>
                            <div class="fw-semibold"><?= e(date_fr($commande['date_livraison'] ?? null)) ?></div>
                        </div>
                        <div class="col-6">
                            <div class="sc-stat-label">Livreur</div>
                            <div class="fw-semibold"><?= e($commande['livreur'] ?? 'À confirmer') ?></div>
                        </div>
                        <?php if (!empty($commande['vehicule'])): ?>
                            <div class="col-6">
                                <div class="sc-stat-label">Véhicule</div>
                                <div class="fw-semibold"><?= e($commande['vehicule']) ?></div>
                            </div>
                        <?php endif; ?>
                        <div class="col-6">
                            <div class="sc-stat-label">Adresse</div>
                            <div class="fw-semibold text-small"><?= e($commande['adresse_livraison']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-box-seam text-sc me-2"></i>Détail de la commande</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr><th>Produit</th><th class="text-end">Qté</th><th class="text-end">Montant</th></tr>
                        </thead>
                        <tbody>
                        <?php $totalMontant = 0.0; $montantConnu = true; $totalSacs = 0; ?>
                        <?php foreach ($lignes as $l): ?>
                            <?php
                            $totalSacs += (int) $l['quantite'];
                            if ($l['prix_unitaire'] === null) {
                                $montantConnu = false;
                            } else {
                                $totalMontant += (float) $l['prix_unitaire'] * (int) $l['quantite'];
                            }
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold text-small"><?= e($l['libelle']) ?></div>
                                    <div class="text-small text-muted"><?= e($l['reference']) ?></div>
                                </td>
                                <td class="text-end sc-num"><?= (int) $l['quantite'] ?></td>
                                <td class="text-end sc-num text-small">
                                    <?= $l['prix_unitaire'] !== null ? e(fcfa((float) $l['prix_unitaire'] * (int) $l['quantite'])) : '—' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th>Total</th>
                                <th class="text-end sc-num"><?= (int) $totalSacs ?></th>
                                <th class="text-end sc-num"><?= $montantConnu ? e(fcfa($totalMontant)) : 'Sur devis' ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="sc-stat-label mb-0">Paiement</span>
                <span class="sc-paiement-badge">
                    <i class="bi bi-<?= e(mode_paiement_icone($commande['mode_paiement'] ?? null)) ?>"></i>
                    <?= e(mode_paiement_libelle($commande['mode_paiement'] ?? null)) ?>
                </span>
            </div>
            <?php if (!empty($commande['commentaire'])): ?>
                <div class="card-footer bg-white">
                    <div class="sc-stat-label">Commentaire transmis</div>
                    <div class="text-small"><?= e($commande['commentaire']) ?></div>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($modifiable): ?>
            <div class="card border-warning">
                <div class="card-body">
                    <h2 class="h6 fw-bold"><i class="bi bi-info-circle text-warning me-1"></i>Commande encore modifiable</h2>
                    <p class="text-small text-muted mb-2">
                        Tant que la commande n'est pas validée, vous pouvez modifier les quantités,
                        la date ou l'adresse, ou annuler la commande.
                    </p>
                    <form method="post" action="<?= e(url('boulanger/commande/annuler')) ?>"
                          data-confirmer="Annuler définitivement cette commande ?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $commande['id'] ?>">
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-x-circle me-1"></i>Annuler la commande
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($modifiable): ?>
    <div class="modal fade" id="modalModifier" tabindex="-1" aria-labelledby="modalModifierLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form method="post" action="<?= e(url('boulanger/commande/modifier')) ?>" class="modal-content">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $commande['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalModifierLabel">Modifier la commande <?= e($commande['reference']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Quantités (0 pour retirer un produit)</label>
                        <?php
                        $quantitesActuelles = [];
                        foreach ($lignes as $l) {
                            $quantitesActuelles[(int) $l['produit_id']] = (int) $l['quantite'];
                        }
                        ?>
                        <?php foreach ($catalogue as $p): ?>
                            <div class="d-flex align-items-center justify-content-between gap-2 border-bottom py-2">
                                <div>
                                    <div class="fw-semibold text-small"><?= e($p['libelle']) ?></div>
                                    <div class="text-small text-muted"><?= e($p['reference']) ?> · <?= (int) $p['stock_disponible'] ?> en stock</div>
                                </div>
                                <input type="number" class="form-control form-control-sm text-center" style="max-width:90px"
                                       name="quantites[<?= (int) $p['id'] ?>]"
                                       value="<?= (int) ($quantitesActuelles[(int) $p['id']] ?? 0) ?>"
                                       min="0" max="9999" inputmode="numeric">
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label" for="m_date">Date de livraison souhaitée</label>
                            <input type="date" class="form-control" id="m_date" name="date_livraison_souhaitee"
                                   value="<?= e($commande['date_livraison_souhaitee']) ?>" min="<?= e($dateMinimum) ?>" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label" for="m_commentaire">Commentaire</label>
                            <input type="text" class="form-control" id="m_commentaire" name="commentaire"
                                   value="<?= e($commande['commentaire'] ?? '') ?>" maxlength="500">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="m_adresse">Adresse de livraison</label>
                            <textarea class="form-control" id="m_adresse" name="adresse_livraison" rows="2"
                                      maxlength="255" required><?= e($commande['adresse_livraison']) ?></textarea>
                        </div>
                        <div class="col-12">
                            <span class="form-label d-block">Mode de paiement</span>
                            <div class="sc-paiement-options">
                                <?php foreach (modes_paiement() as $code => $mode): ?>
                                    <div class="sc-paiement-option">
                                        <input type="radio" id="m_paiement-<?= e($code) ?>" name="mode_paiement"
                                               value="<?= e($code) ?>" <?= ($commande['mode_paiement'] ?? 'carte') === $code ? 'checked' : '' ?> required>
                                        <label for="m_paiement-<?= e($code) ?>">
                                            <span class="sc-paiement-ico"><i class="bi bi-<?= e($mode[1]) ?>"></i></span>
                                            <span class="sc-paiement-txt">
                                                <strong><?= e($mode[0]) ?></strong>
                                                <small><?= e($mode[2]) ?></small>
                                            </span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-sc"><i class="bi bi-save me-1"></i>Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
