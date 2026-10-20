<?php
/**
 * F08 : historique complet des commandes du boulanger.
 * @var array  $commandes
 * @var array  $compteurs
 * @var string $statut
 * @var string $q
 */
$onglets = [
    ''               => ['Toutes',          'grid'],
    'recue'          => ['Reçues',          'inbox'],
    'validee'        => ['Validées',        'check2-circle'],
    'en_preparation' => ['En préparation',  'box-seam'],
    'en_livraison'   => ['En livraison',    'truck'],
    'livree'         => ['Livrées',         'check2-all'],
    'refusee'        => ['Refusées',        'x-octagon'],
    'annulee'        => ['Annulées',        'slash-circle'],
];
$totalGlobal = array_sum($compteurs);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-0">Mes commandes</h1>
        <p class="text-muted small mb-0"><?= (int) $totalGlobal ?> commande(s) enregistrée(s) depuis la création de votre compte.</p>
    </div>
    <a href="<?= e(url('boulanger/commande/nouvelle')) ?>" class="btn btn-nma">
        <i class="bi bi-plus-circle me-2"></i>Nouvelle commande
    </a>
</div>

<form method="get" action="<?= e(url('boulanger/historique')) ?>" class="card mb-3">
    <input type="hidden" name="r" value="boulanger/historique">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <label class="form-label small" for="q">Rechercher une référence</label>
                <input type="search" class="form-control" id="q" name="q" value="<?= e($q) ?>" placeholder="CMD-2026-...">
            </div>
            <div class="col-8 col-md-4">
                <label class="form-label small" for="statut">Statut</label>
                <select class="form-select" id="statut" name="statut">
                    <?php foreach ($onglets as $cle => $info): ?>
                        <option value="<?= e($cle) ?>" <?= $statut === $cle ? 'selected' : '' ?>>
                            <?= e($info[0]) ?><?= $cle !== '' ? ' (' . (int) ($compteurs[$cle] ?? 0) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-4 col-md-3">
                <button type="submit" class="btn btn-nma w-100"><i class="bi bi-funnel me-1"></i>Filtrer</button>
            </div>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <?php if ($commandes === []): ?>
            <div class="nma-empty">
                <i class="bi bi-receipt"></i>
                Aucune commande ne correspond à ce filtre.
                <div class="mt-2"><a href="<?= e(url('boulanger/historique')) ?>" class="btn btn-sm btn-outline-nma">Voir toutes mes commandes</a></div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th class="d-none d-md-table-cell">Commandée le</th>
                            <th>Livraison</th>
                            <th class="text-end">Sacs</th>
                            <th>Statut</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($commandes as $c): ?>
                        <tr>
                            <td>
                                <a href="<?= e(url('boulanger/commande', ['id' => (int) $c['id']])) ?>" class="fw-semibold">
                                    <?= e($c['reference']) ?>
                                </a>
                                <?php if (!empty($c['motif_refus'])): ?>
                                    <div class="text-small text-danger">Motif : <?= e($c['motif_refus']) ?></div>
                                <?php endif; ?>
                                <div class="text-small text-muted">
                                    <i class="bi bi-<?= e(mode_paiement_icone($c['mode_paiement'] ?? null)) ?> me-1"></i><?= e(mode_paiement_libelle($c['mode_paiement'] ?? null)) ?>
                                </div>
                            </td>
                            <td class="d-none d-md-table-cell text-small text-muted"><?= e(date_fr($c['date_commande'], 'd/m/Y')) ?></td>
                            <td class="text-small">
                                <?php if (!empty($c['date_livraison'])): ?>
                                    <i class="bi bi-calendar-check text-success me-1"></i><?= e(date_fr($c['date_livraison'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">souhaitée le <?= e(date_fr($c['date_livraison_souhaitee'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end nma-num"><?= (int) $c['total_sacs'] ?></td>
                            <td><?= statut_badge($c['statut']) ?></td>
                            <td class="text-end">
                                <a href="<?= e(url('boulanger/commande', ['id' => (int) $c['id']])) ?>"
                                   class="btn btn-sm btn-outline-nma">
                                    <i class="bi bi-eye"></i><span class="d-none d-lg-inline ms-1">Suivre</span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
