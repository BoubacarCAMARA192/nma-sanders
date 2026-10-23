<?php
/**
 * Tableau de bord gestionnaire : pilotage des commandes et des livraisons (F15).
 * @var array $compteurs
 * @var array $aTraiter
 * @var array $aPlanifier
 * @var array $livraisons
 * @var array $alertes
 * @var array $stats
 * @var array $livrStats
 */
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-0">Pilotage des commandes</h1>
        <p class="text-muted small mb-0">Vue d'ensemble au <?= e(datetime_fr(date('Y-m-d H:i:s'))) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(url('gestionnaire/export')) ?>" class="btn btn-outline-sc">
            <i class="bi bi-filetype-csv me-1"></i>Export CSV
        </a>
        <a href="<?= e(url('gestionnaire/commandes', ['statut' => 'recue'])) ?>" class="btn btn-sc">
            <i class="bi bi-inbox me-1"></i>À traiter
            <?php if ($compteurs['recue'] > 0): ?>
                <span class="badge text-bg-light ms-1"><?= (int) $compteurs['recue'] ?></span>
            <?php endif; ?>
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php
    $cartes = [
        ['Commandes reçues',    $compteurs['recue'],          'inbox',         'warning'],
        ['À préparer',          $compteurs['validee'] + $compteurs['en_preparation'], 'box-seam', 'info'],
        ['En livraison',        $compteurs['en_livraison'],   'truck',         'primary'],
        ['Livrées',             $compteurs['livree'],         'check2-all',    'success'],
    ];
    foreach ($cartes as [$libelle, $valeur, $icone, $couleur]): ?>
        <div class="col-6 col-lg-3">
            <div class="sc-stat d-flex align-items-center gap-3">
                <div class="sc-stat-ico text-<?= e($couleur) ?>"><i class="bi bi-<?= e($icone) ?>"></i></div>
                <div>
                    <div class="sc-stat-value"><?= (int) $valeur ?></div>
                    <div class="sc-stat-label"><?= e($libelle) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-7">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-inbox text-danger me-2"></i>Nouvelles commandes à valider</span>
                <a href="<?= e(url('gestionnaire/commandes')) ?>" class="small">Toutes les commandes</a>
            </div>
            <div class="card-body p-0">
                <?php if ($aTraiter === []): ?>
                    <div class="sc-empty"><i class="bi bi-check2-circle"></i>Aucune commande en attente. Tout est à jour !</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Référence</th>
                                    <th class="d-none d-sm-table-cell">Boulangerie</th>
                                    <th class="text-end">Sacs</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($aTraiter as $c): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= e($c['reference']) ?></div>
                                        <div class="text-small text-muted"><?= e(date_fr($c['date_commande'], 'd/m H\hi')) ?></div>
                                    </td>
                                    <td class="d-none d-sm-table-cell text-small">
                                        <?= e($c['nom_boulangerie']) ?>
                                        <div class="text-muted"><?= e($c['ville'] ?? '') ?></div>
                                    </td>
                                    <td class="text-end sc-num"><?= (int) $c['total_sacs'] ?></td>
                                    <td class="text-end">
                                        <a href="<?= e(url('gestionnaire/commande', ['id' => (int) $c['id']])) ?>"
                                           class="btn btn-sm btn-sc">Traiter</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($aPlanifier !== []): ?>
            <div class="card mb-3">
                <div class="card-header text-warning">
                    <i class="bi bi-calendar-plus me-2"></i>Livraisons à planifier (<?= count($aPlanifier) ?>)
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach (array_slice($aPlanifier, 0, 5) as $c): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                                <div>
                                    <div class="fw-semibold text-small"><?= e($c['reference']) ?> — <?= e($c['nom_boulangerie']) ?></div>
                                    <div class="text-small text-muted">
                                        <?= (int) $c['total_sacs'] ?> sac(s) · souhaitée le <?= e(date_fr($c['date_livraison_souhaitee'])) ?>
                                    </div>
                                </div>
                                <a href="<?= e(url('gestionnaire/commande', ['id' => (int) $c['id']])) ?>#livraison" class="btn btn-sm btn-outline-sc">
                                    <i class="bi bi-truck"></i><span class="d-none d-md-inline ms-1">Planifier</span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-12 col-xl-5">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-truck text-sc me-2"></i>Livraisons du jour</div>
            <div class="card-body p-0">
                <?php if ($livraisons === []): ?>
                    <div class="sc-empty"><i class="bi bi-calendar-x"></i>Aucune tournée programmée aujourd'hui.</div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($livraisons as $l): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between gap-2">
                                    <div>
                                        <div class="fw-semibold text-small"><?= e($l['nom_boulangerie']) ?></div>
                                        <div class="text-small text-muted"><?= e($l['reference']) ?> · <?= (int) $l['total_sacs'] ?> sac(s)</div>
                                        <div class="text-small text-muted"><i class="bi bi-person-badge me-1"></i><?= e($l['livreur'] ?? 'Livreur à affecter') ?></div>
                                    </div>
                                    <span class="badge text-bg-<?= $l['etat'] === 'livree' ? 'success' : ($l['etat'] === 'en_cours' ? 'primary' : 'secondary') ?> align-self-start">
                                        <?= e(ucfirst(str_replace('_', ' ', (string) $l['etat']))) ?>
                                    </span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white text-center">
                <a href="<?= e(url('gestionnaire/livraisons')) ?>" class="small">Voir le planning complet</a>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-speedometer2 text-sc me-2"></i>Indicateurs clés</div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Volume livré (6 mois)</span>
                    <strong><?= (int) $stats['sacs'] ?> sacs</strong>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Taux de livraison</span>
                    <strong><?= e((string) $stats['taux_livraison']) ?> %</strong>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Délai moyen commande → livraison</span>
                    <strong><?= $stats['delai_moyen'] !== null ? e((string) $stats['delai_moyen']) . ' h' : '—' ?></strong>
                </div>
                <div class="sc-sep"></div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Tournées en cours</span><strong><?= (int) $livrStats['en_cours'] ?></strong>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Livraisons à planifier</span><strong><?= (int) $livrStats['a_planifier'] ?></strong>
                </div>
            </div>
            <div class="card-footer bg-white text-center">
                <a href="<?= e(url('gestionnaire/statistiques')) ?>" class="small">Analyse détaillée</a>
            </div>
        </div>

        <?php if ($alertes !== []): ?>
            <div class="card border-warning">
                <div class="card-header text-warning"><i class="bi bi-exclamation-triangle me-2"></i>Stock sous le seuil d'alerte</div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach (array_slice($alertes, 0, 5) as $p): ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-small"><?= e($p['libelle']) ?></span>
                                <span class="badge text-bg-warning"><?= (int) $p['stock_disponible'] ?> / seuil <?= (int) $p['seuil_alerte'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
