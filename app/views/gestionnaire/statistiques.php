<?php
/**
 * F15 : statistiques des commandes et des livraisons.
 * @var string $depuis
 * @var string $jusqu
 * @var array  $stats
 * @var array  $livrStats
 */
$maxSacs = 1;
foreach ($stats['par_mois'] as $m) {
    $maxSacs = max($maxSacs, (int) $m['total_sacs']);
}
$totalStatuts = max(1, array_sum($stats['par_statut']));
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-0">Statistiques</h1>
        <p class="text-muted small mb-0">Période analysée : du <?= e(date_fr($depuis)) ?> au <?= e(date_fr($jusqu)) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(url('gestionnaire/export', ['depuis' => $depuis, 'jusqu' => $jusqu])) ?>" class="btn btn-outline-nma">
            <i class="bi bi-filetype-csv me-1"></i>Exporter la période
        </a>
    </div>
</div>

<form method="get" action="<?= e(url('gestionnaire/statistiques')) ?>" class="card mb-3">
    <input type="hidden" name="r" value="gestionnaire/statistiques">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-5 col-md-3">
                <label class="form-label small" for="depuis">Du</label>
                <input type="date" class="form-control" id="depuis" name="depuis" value="<?= e($depuis) ?>">
            </div>
            <div class="col-5 col-md-3">
                <label class="form-label small" for="jusqu">Au</label>
                <input type="date" class="form-control" id="jusqu" name="jusqu" value="<?= e($jusqu) ?>">
            </div>
            <div class="col-2 col-md-2">
                <button type="submit" class="btn btn-nma w-100"><i class="bi bi-arrow-repeat"></i></button>
            </div>
        </div>
    </div>
</form>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="nma-stat">
            <div class="nma-stat-value"><?= (int) $stats['total'] ?></div>
            <div class="nma-stat-label">Commandes sur la période</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="nma-stat">
            <div class="nma-stat-value"><?= (int) $stats['sacs'] ?></div>
            <div class="nma-stat-label">Sacs commandés</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="nma-stat">
            <div class="nma-stat-value"><?= e((string) $stats['taux_livraison']) ?> %</div>
            <div class="nma-stat-label">Commandes livrées</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="nma-stat">
            <div class="nma-stat-value"><?= $stats['delai_moyen'] !== null ? e((string) $stats['delai_moyen']) . ' h' : '—' ?></div>
            <div class="nma-stat-label">Délai moyen commande → livraison</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-graph-up text-nma me-2"></i>Volumes par mois (6 derniers mois)</div>
            <div class="card-body">
                <?php if ($stats['par_mois'] === []): ?>
                    <p class="text-muted small mb-0">Pas encore de données suffisantes.</p>
                <?php else: ?>
                    <?php foreach ($stats['par_mois'] as $m): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between text-small">
                                <span class="fw-semibold">
                                    <?= e(periode_fr($m['periode'])) ?>
                                </span>
                                <span class="text-muted">
                                    <?= (int) $m['nb_commandes'] ?> commande(s) · <strong><?= (int) $m['total_sacs'] ?> sacs</strong>
                                </span>
                            </div>
                            <div class="progress nma-progress mt-1">
                                <div class="progress-bar" style="width: <?= (int) round((int) $m['total_sacs'] * 100 / $maxSacs) ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-pie-chart text-nma me-2"></i>Répartition par statut (toutes périodes)</div>
            <div class="card-body p-0">
                <table class="table mb-0 align-middle">
                    <tbody>
                    <?php foreach ($stats['par_statut'] as $statut => $total): ?>
                        <?php [$libelle, $couleur] = statut_meta($statut); ?>
                        <tr>
                            <td style="width:180px"><?= statut_badge($statut) ?></td>
                            <td class="nma-num text-end" style="width:70px"><?= (int) $total ?></td>
                            <td>
                                <div class="progress nma-progress">
                                    <div class="progress-bar bg-<?= e($couleur) ?>" style="width: <?= (int) round((int) $total * 100 / $totalStatuts) ?>%"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-trophy text-nma me-2"></i>Produits les plus commandés</div>
            <div class="card-body p-0">
                <?php if ($stats['top_produits'] === []): ?>
                    <div class="nma-empty"><i class="bi bi-box"></i>Aucune donnée sur la période.</div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($stats['top_produits'] as $i => $p): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge text-bg-light me-2"><?= $i + 1 ?></span>
                                    <span class="text-small fw-semibold"><?= e($p['libelle']) ?></span>
                                    <div class="text-small text-muted"><?= (int) $p['nb_commandes'] ?> commande(s)</div>
                                </div>
                                <span class="nma-pill"><?= (int) $p['total_sacs'] ?> sacs</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-shop text-nma me-2"></i>Meilleurs clients</div>
            <div class="card-body p-0">
                <?php if ($stats['top_boulangers'] === []): ?>
                    <div class="nma-empty"><i class="bi bi-people"></i>Aucune donnée sur la période.</div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($stats['top_boulangers'] as $i => $b): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge text-bg-light me-2"><?= $i + 1 ?></span>
                                    <span class="text-small fw-semibold"><?= e($b['nom_boulangerie']) ?></span>
                                    <div class="text-small text-muted"><?= e($b['ville'] ?? '') ?> · <?= (int) $b['nb_commandes'] ?> commande(s)</div>
                                </div>
                                <span class="nma-pill"><?= (int) $b['total_sacs'] ?> sacs</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-truck text-nma me-2"></i>Livraisons</div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Planifiées</span><strong><?= (int) $livrStats['planifiees'] ?></strong></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">En cours</span><strong><?= (int) $livrStats['en_cours'] ?></strong></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Livrées</span><strong><?= (int) $livrStats['livrees'] ?></strong></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Échouées</span><strong><?= (int) $livrStats['echouees'] ?></strong></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">À planifier</span><strong><?= (int) $livrStats['a_planifier'] ?></strong></div>
            </div>
        </div>
    </div>
</div>
