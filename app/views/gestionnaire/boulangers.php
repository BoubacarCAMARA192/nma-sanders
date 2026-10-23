<?php
/**
 * Suivi commercial : liste des boulangeries clientes (90 derniers jours).
 * @var array $boulangers
 */
$totalSacs = 0;
foreach ($boulangers as $b) {
    $totalSacs += (int) $b['total_sacs'];
}
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-0">Mes clients boulangers</h1>
        <p class="text-muted small mb-0">
            <?= count($boulangers) ?> client(s) · <?= (int) $totalSacs ?> sac(s) commandés sur 90 jours
        </p>
    </div>
    <a href="<?= e(url('gestionnaire/export')) ?>" class="btn btn-outline-sc">
        <i class="bi bi-filetype-csv me-1"></i>Export CSV
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if ($boulangers === []): ?>
            <div class="sc-empty"><i class="bi bi-people"></i>Aucun client boulanger enregistré.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Boulangerie</th>
                            <th class="d-none d-md-table-cell">Contact</th>
                            <th class="d-none d-lg-table-cell">Zone</th>
                            <th class="text-end">Commandes</th>
                            <th class="text-end">Sacs (90 j)</th>
                            <th class="d-none d-lg-table-cell">Dernière commande</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($boulangers as $b): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e($b['nom_boulangerie']) ?></div>
                                <div class="text-small text-muted"><?= e($b['adresse']) ?></div>
                                <?php if ((int) $b['actif'] !== 1): ?>
                                    <span class="badge text-bg-secondary">Compte inactif</span>
                                <?php endif; ?>
                            </td>
                            <td class="d-none d-md-table-cell text-small">
                                <?= e($b['contact']) ?>
                                <div class="text-muted">
                                    <a href="tel:<?= e($b['telephone'] ?? '') ?>"><i class="bi bi-telephone me-1"></i><?= e($b['telephone'] ?? '—') ?></a>
                                </div>
                            </td>
                            <td class="d-none d-lg-table-cell text-small">
                                <?= e($b['ville'] ?? '') ?>
                                <?php if (!empty($b['zone_livraison'])): ?>
                                    <div class="text-muted"><?= e($b['zone_livraison']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end sc-num"><?= (int) $b['nb_commandes'] ?></td>
                            <td class="text-end sc-num fw-semibold"><?= (int) $b['total_sacs'] ?></td>
                            <td class="d-none d-lg-table-cell text-small text-muted">
                                <?= $b['derniere_commande'] !== null ? e(date_fr($b['derniere_commande'])) : 'Jamais' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
