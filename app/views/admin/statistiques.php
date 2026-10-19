<?php
/**
 * Statistiques globales de la plateforme (administrateur).
 * @var string $depuis
 * @var string $jusqu
 * @var array  $stats
 * @var array  $users
 * @var array  $produits
 * @var array  $livrStats
 * @var array  $boulangers
 */
$maxSacs = 1;
foreach ($stats['par_mois'] as $m) {
    $maxSacs = max($maxSacs, (int) $m['total_sacs']);
}
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-0">Statistiques globales</h1>
        <p class="text-muted small mb-0">Du <?= e(date_fr($depuis)) ?> au <?= e(date_fr($jusqu)) ?></p>
    </div>
</div>

<form method="get" action="<?= e(url('administrateur/statistiques')) ?>" class="card mb-3">
    <input type="hidden" name="r" value="administrateur/statistiques">
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
    <div class="col-6 col-lg-2">
        <div class="nma-stat"><div class="nma-stat-value"><?= (int) $stats['total'] ?></div><div class="nma-stat-label">Commandes</div></div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="nma-stat"><div class="nma-stat-value"><?= (int) $stats['sacs'] ?></div><div class="nma-stat-label">Sacs</div></div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="nma-stat"><div class="nma-stat-value"><?= e((string) $stats['taux_livraison']) ?> %</div><div class="nma-stat-label">Taux de livraison</div></div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="nma-stat"><div class="nma-stat-value"><?= $stats['delai_moyen'] !== null ? e((string) $stats['delai_moyen']) . ' h' : '—' ?></div><div class="nma-stat-label">Délai moyen</div></div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="nma-stat"><div class="nma-stat-value"><?= (int) $users['actifs'] ?></div><div class="nma-stat-label">Comptes actifs</div></div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="nma-stat"><div class="nma-stat-value"><?= (int) $produits['stock'] ?></div><div class="nma-stat-label">Stock (sacs)</div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-graph-up text-nma me-2"></i>Volumes mensuels</div>
            <div class="card-body">
                <?php if ($stats['par_mois'] === []): ?>
                    <p class="text-muted small mb-0">Pas encore de données suffisantes.</p>
                <?php else: ?>
                    <?php foreach ($stats['par_mois'] as $m): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between text-small">
                                <span class="fw-semibold"><?= e(periode_fr($m['periode'])) ?></span>
                                <span class="text-muted"><?= (int) $m['nb_commandes'] ?> commande(s) · <strong><?= (int) $m['total_sacs'] ?> sacs</strong></span>
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
            <div class="card-header"><i class="bi bi-people text-nma me-2"></i>Comptes de la plateforme</div>
            <div class="card-body">
                <div class="row g-3 text-center">
                    <div class="col-4">
                        <div class="nma-stat-value"><?= (int) $users['boulangers'] ?></div>
                        <div class="nma-stat-label">Boulangers actifs</div>
                    </div>
                    <div class="col-4">
                        <div class="nma-stat-value"><?= (int) $users['gestionnaires'] ?></div>
                        <div class="nma-stat-label">Gestionnaires</div>
                    </div>
                    <div class="col-4">
                        <div class="nma-stat-value"><?= (int) $users['admins'] ?></div>
                        <div class="nma-stat-label">Administrateurs</div>
                    </div>
                </div>
                <div class="nma-sep"></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Comptes en attente d'activation</span><strong><?= (int) $users['inactifs'] ?></strong></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Produits sous le seuil d'alerte</span><strong><?= (int) $produits['alerte'] ?></strong></div>
            </div>
            <div class="card-footer bg-white text-center">
                <a href="<?= e(url('administrateur/utilisateurs', ['role' => ''])) ?>" class="small">Gérer les utilisateurs</a>
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
                                    <div class="text-small text-muted"><?= e($p['reference']) ?></div>
                                </div>
                                <span class="nma-pill"><?= (int) $p['total_sacs'] ?> sacs</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-shop text-nma me-2"></i>Plus gros volumes (30 jours)</div>
            <div class="card-body p-0">
                <?php if ($boulangers === []): ?>
                    <div class="nma-empty"><i class="bi bi-people"></i>Aucune activité récente.</div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach (array_slice($boulangers, 0, 6) as $b): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
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
