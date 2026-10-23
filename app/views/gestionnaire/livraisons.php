<?php
/**
 * F13 : planning des livraisons (jour, tournees a venir, a planifier).
 * @var string $date
 * @var array  $planning
 * @var array  $aPlanifier
 * @var array  $aVenir
 * @var array  $stats
 * @var array  $livreurs
 */
$etatBadge = ['planifiee' => 'secondary', 'en_cours' => 'primary', 'livree' => 'success', 'echouee' => 'danger'];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-0">Planning des livraisons</h1>
        <p class="text-muted small mb-0">Tournées du <?= e(date_fr($date, 'd/m/Y')) ?></p>
    </div>
    <form method="get" action="<?= e(url('gestionnaire/livraisons')) ?>" class="d-flex gap-2">
        <input type="hidden" name="r" value="gestionnaire/livraisons">
        <input type="date" class="form-control" name="date" value="<?= e($date) ?>">
        <button type="submit" class="btn btn-sc text-nowrap"><i class="bi bi-search"></i></button>
    </form>
</div>

<div class="row g-3 mb-4">
    <?php
    $cartes = [
        ['Planifiées',     $stats['planifiees'], 'calendar-check', 'secondary'],
        ['En cours',       $stats['en_cours'],   'truck',          'primary'],
        ['Livrées',        $stats['livrees'],    'check2-all',     'success'],
        ['À planifier',    $stats['a_planifier'],'calendar-plus',  'warning'],
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

<?php if ($aPlanifier !== []): ?>
    <div class="card mb-3 border-warning">
        <div class="card-header bg-warning-subtle"><i class="bi bi-calendar-plus me-2"></i>Commandes validées sans tournée planifiée (<?= count($aPlanifier) ?>)</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Boulangerie</th>
                            <th>Livraison souhaitée</th>
                            <th class="text-end">Sacs</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($aPlanifier as $c): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($c['reference']) ?></td>
                            <td class="text-small">
                                <?= e($c['nom_boulangerie']) ?>
                                <div class="text-muted"><?= e($c['zone_livraison'] ?? '') ?></div>
                            </td>
                            <td class="text-small"><?= e(date_fr($c['date_livraison_souhaitee'])) ?></td>
                            <td class="text-end sc-num"><?= (int) $c['total_sacs'] ?></td>
                            <td class="text-end">
                                <a href="<?= e(url('gestionnaire/commande', ['id' => (int) $c['id']])) ?>#livraison"
                                   class="btn btn-sm btn-sc"><i class="bi bi-truck me-1"></i>Planifier</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-truck text-sc me-2"></i>Tournées du <?= e(date_fr($date)) ?></div>
    <div class="card-body p-0">
        <?php if ($planning === []): ?>
            <div class="sc-empty"><i class="bi bi-calendar-x"></i>Aucune livraison programmée pour cette date.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Commande</th>
                            <th>Boulangerie / adresse</th>
                            <th class="d-none d-md-table-cell">Livreur</th>
                            <th class="text-end">Sacs</th>
                            <th>État</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($planning as $l): ?>
                        <tr>
                            <td>
                                <a href="<?= e(url('gestionnaire/commande', ['id' => (int) $l['commande_id']])) ?>" class="fw-semibold">
                                    <?= e($l['reference']) ?>
                                </a>
                                <div class="text-small text-muted"><?= e($l['contact']) ?> · <?= e($l['telephone'] ?? '') ?></div>
                            </td>
                            <td class="text-small">
                                <?= e($l['nom_boulangerie']) ?>
                                <div class="text-muted"><?= e($l['adresse_livraison']) ?></div>
                            </td>
                            <td class="d-none d-md-table-cell text-small">
                                <?= e($l['livreur'] ?? '—') ?>
                                <?php if (!empty($l['vehicule'])): ?>
                                    <div class="text-muted"><?= e($l['vehicule']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end sc-num"><?= (int) $l['total_sacs'] ?></td>
                            <td>
                                <span class="badge text-bg-<?= e($etatBadge[$l['etat']] ?? 'secondary') ?>">
                                    <?= e(ucfirst(str_replace('_', ' ', (string) $l['etat']))) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <form method="post" action="<?= e(url('gestionnaire/livraisons/etat')) ?>" class="d-flex gap-1 justify-content-end">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="commande_id" value="<?= (int) $l['commande_id'] ?>">
                                    <select class="form-select form-select-sm" name="etat" style="max-width:130px">
                                        <?php foreach (['planifiee' => 'Planifiée', 'en_cours' => 'En cours', 'livree' => 'Livrée', 'echouee' => 'Échouée'] as $cle => $lib): ?>
                                            <option value="<?= e($cle) ?>" <?= $l['etat'] === $cle ? 'selected' : '' ?>><?= e($lib) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-outline-sc"><i class="bi bi-check2"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-calendar-week text-sc me-2"></i>Prochaines tournées</div>
    <div class="card-body p-0">
        <?php if ($aVenir === []): ?>
            <div class="sc-empty"><i class="bi bi-calendar3"></i>Aucune tournée à venir.</div>
        <?php else: ?>
            <ul class="list-group list-group-flush">
                <?php foreach (array_slice($aVenir, 0, 10) as $l): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                        <div>
                            <div class="fw-semibold text-small"><?= e(date_fr($l['date_livraison'], 'd/m/Y')) ?> — <?= e($l['nom_boulangerie']) ?></div>
                            <div class="text-small text-muted">
                                <?= e($l['reference']) ?> · <?= (int) $l['total_sacs'] ?> sac(s)
                                <?= !empty($l['livreur']) ? ' · ' . e($l['livreur']) : ' · livreur à affecter' ?>
                            </div>
                        </div>
                        <a href="<?= e(url('gestionnaire/commande', ['id' => (int) $l['commande_id']])) ?>#livraison"
                           class="btn btn-sm btn-outline-sc"><i class="bi bi-pencil"></i></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
