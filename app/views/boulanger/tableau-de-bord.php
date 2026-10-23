<?php
/**
 * Tableau de bord boulanger : situation des commandes en cours et raccourcis.
 * @var array $compteurs
 * @var array $enCours
 * @var array $dernieres
 * @var array $reappro
 * @var array $alertes
 * @var int   $nbPanier
 */
$totalActives = $compteurs['recue'] + $compteurs['validee'] + $compteurs['en_preparation'] + $compteurs['en_livraison'];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-0">Bonjour <?= e(explode(' ', (string) $utilisateur['nom'])[0]) ?> 👋</h1>
        <p class="text-muted small mb-0"><?= e($utilisateur['nom_boulangerie'] ?? '') ?><?= !empty($utilisateur['zone_livraison']) ? ' · ' . e($utilisateur['zone_livraison']) : '' ?></p>
    </div>
    <a href="<?= e(url('boulanger/commande/nouvelle')) ?>" class="btn btn-sc">
        <i class="bi bi-plus-circle me-2"></i>Passer une commande
        <?php if ($nbPanier > 0): ?><span class="badge text-bg-light ms-1"><?= (int) $nbPanier ?></span><?php endif; ?>
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="sc-stat d-flex align-items-center gap-3">
            <div class="sc-stat-ico"><i class="bi bi-truck"></i></div>
            <div>
                <div class="sc-stat-value"><?= (int) $totalActives ?></div>
                <div class="sc-stat-label">Commande(s) en cours</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="sc-stat d-flex align-items-center gap-3">
            <div class="sc-stat-ico"><i class="bi bi-inbox"></i></div>
            <div>
                <div class="sc-stat-value"><?= (int) $compteurs['recue'] ?></div>
                <div class="sc-stat-label">En attente de validation</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="sc-stat d-flex align-items-center gap-3">
            <div class="sc-stat-ico"><i class="bi bi-check2-all"></i></div>
            <div>
                <div class="sc-stat-value"><?= (int) $compteurs['livree'] ?></div>
                <div class="sc-stat-label">Commandes livrées</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="sc-stat d-flex align-items-center gap-3">
            <div class="sc-stat-ico"><i class="bi bi-arrow-repeat"></i></div>
            <div>
                <div class="sc-stat-value"><?= (int) ($compteurs['refusee'] + $compteurs['annulee']) ?></div>
                <div class="sc-stat-label">Refusées / annulées</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-truck text-sc me-2"></i>Commandes en cours de traitement</span>
                <a href="<?= e(url('boulanger/historique')) ?>" class="small">Tout voir</a>
            </div>
            <div class="card-body p-0">
                <?php if ($enCours === []): ?>
                    <div class="sc-empty">
                        <i class="bi bi-clipboard-check"></i>
                        Aucune commande en cours.
                        <div class="mt-2">
                            <a href="<?= e(url('boulanger/catalogue')) ?>" class="btn btn-sm btn-outline-sc">Voir le catalogue</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($enCours as $c): ?>
                            <a class="list-group-item list-group-item-action py-3"
                               href="<?= e(url('boulanger/commande', ['id' => (int) $c['id']])) ?>">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="fw-semibold"><?= e($c['reference']) ?></div>
                                        <div class="text-small text-muted">
                                            <?= (int) $c['total_sacs'] ?> sac(s) · livraison souhaitée le <?= e(date_fr($c['date_livraison_souhaitee'])) ?>
                                        </div>
                                        <?php if (!empty($c['date_livraison'])): ?>
                                            <div class="text-small text-muted">
                                                <i class="bi bi-calendar-check me-1"></i>Tournée planifiée le <?= e(date_fr($c['date_livraison'])) ?><?= !empty($c['livreur']) ? ' — ' . e($c['livreur']) : '' ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-end"><?= statut_badge($c['statut']) ?></div>
                                </div>
                                <div class="progress sc-progress mt-2">
                                    <div class="progress-bar" style="width: <?= statut_progression($c['statut']) ?>%"></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-arrow-clockwise text-sc me-2"></i>Réapprovisionnement express</div>
            <div class="card-body">
                <?php if ($reappro === []): ?>
                    <p class="text-muted small mb-0">Vos produits habituels apparaîtront ici après votre première commande.</p>
                <?php else: ?>
                    <p class="text-muted small">Reprenez vos derniers produits en un clic :</p>
                    <?php foreach ($reappro as $ligne): ?>
                        <form method="post" action="<?= e(url('boulanger/panier/ajouter')) ?>" class="d-flex align-items-center gap-2 mb-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="produit_id" value="<?= (int) $ligne['id'] ?>">
                            <input type="hidden" name="quantite" value="<?= max(1, (int) round((float) $ligne['quantite_cumulee'] / 4)) ?>">
                            <input type="hidden" name="retour" value="boulanger/commande/nouvelle">
                            <div class="flex-grow-1">
                                <div class="fw-semibold text-small"><?= e($ligne['libelle']) ?></div>
                                <div class="text-small text-muted">Dernière commande : <?= e(date_fr($ligne['derniere_commande'])) ?></div>
                            </div>
                            <button type="submit" class="btn btn-sm btn-outline-sc">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </form>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($alertes !== []): ?>
            <div class="card">
                <div class="card-header text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Disponibilité limitée</div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach (array_slice($alertes, 0, 4) as $p): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-small"><?= e($p['libelle']) ?></span>
                                <span class="badge text-bg-warning"><?= (int) $p['stock_disponible'] ?> restant(s)</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header"><i class="bi bi-clock-history text-sc me-2"></i>Mes 5 dernières commandes</div>
    <div class="card-body p-0">
        <?php if ($dernieres === []): ?>
            <div class="sc-empty"><i class="bi bi-receipt"></i>Aucune commande enregistrée pour le moment.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th class="d-none d-sm-table-cell">Date</th>
                            <th>Livraison</th>
                            <th class="text-end">Sacs</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($dernieres as $c): ?>
                        <tr>
                            <td>
                                <a href="<?= e(url('boulanger/commande', ['id' => (int) $c['id']])) ?>" class="fw-semibold">
                                    <?= e($c['reference']) ?>
                                </a>
                            </td>
                            <td class="d-none d-sm-table-cell text-small text-muted"><?= e(date_fr($c['date_commande'])) ?></td>
                            <td class="text-small"><?= e(date_fr($c['date_livraison_souhaitee'])) ?></td>
                            <td class="text-end sc-num"><?= (int) $c['total_sacs'] ?></td>
                            <td><?= statut_badge($c['statut']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
