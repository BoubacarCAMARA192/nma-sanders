<?php
/**
 * F10 : liste des commandes a traiter avec filtres.
 * @var array $commandes
 * @var array $filtres
 * @var array $compteurs
 */
$statuts = [
    ''               => 'Tous les statuts',
    'a_traiter'      => 'À traiter (reçues)',
    'recue'          => 'Reçues',
    'validee'        => 'Validées',
    'en_preparation' => 'En préparation',
    'en_livraison'   => 'En livraison',
    'livree'         => 'Livrées',
    'refusee'        => 'Refusées',
    'annulee'        => 'Annulées',
];
$totalSacs = 0;
foreach ($commandes as $c) {
    $totalSacs += (int) $c['total_sacs'];
}
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-0">Commandes</h1>
        <p class="text-muted small mb-0">
            <?= count($commandes) ?> commande(s) affichée(s) · <?= (int) $totalSacs ?> sac(s)
        </p>
    </div>
    <a href="<?= e(url('gestionnaire/export', $filtres)) ?>" class="btn btn-outline-nma">
        <i class="bi bi-filetype-csv me-1"></i>Exporter cette liste (CSV)
    </a>
</div>

<form method="get" action="<?= e(url('gestionnaire/commandes')) ?>" class="card mb-3">
    <input type="hidden" name="r" value="gestionnaire/commandes">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-lg-3">
                <label class="form-label small" for="statut">Statut</label>
                <select class="form-select" id="statut" name="statut" data-filtre-auto>
                    <?php foreach ($statuts as $cle => $libelle): ?>
                        <option value="<?= e($cle) ?>" <?= ($filtres['statut'] ?? '') === $cle ? 'selected' : '' ?>>
                            <?= e($libelle) ?><?= $cle === 'recue' ? ' (' . (int) $compteurs['recue'] . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label small" for="depuis">Du</label>
                <input type="date" class="form-control" id="depuis" name="depuis" value="<?= e($filtres['depuis'] ?? '') ?>">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label small" for="jusqu">Au</label>
                <input type="date" class="form-control" id="jusqu" name="jusqu" value="<?= e($filtres['jusqu'] ?? '') ?>">
            </div>
            <div class="col-6 col-lg-3">
                <label class="form-label small" for="q">Recherche</label>
                <input type="search" class="form-control" id="q" name="q" value="<?= e($filtres['q'] ?? '') ?>"
                       placeholder="Référence, boulangerie...">
            </div>
            <div class="col-12 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-nma flex-grow-1"><i class="bi bi-funnel"></i></button>
                <a href="<?= e(url('gestionnaire/commandes')) ?>" class="btn btn-outline-secondary" title="Réinitialiser">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <?php if ($commandes === []): ?>
            <div class="nma-empty"><i class="bi bi-inbox"></i>Aucune commande ne correspond à ces critères.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Boulangerie</th>
                            <th class="d-none d-lg-table-cell">Reçue le</th>
                            <th class="d-none d-md-table-cell">Livraison</th>
                            <th class="text-end">Sacs</th>
                            <th>Statut</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($commandes as $c): ?>
                        <tr class="<?= $c['statut'] === 'recue' ? 'table-warning' : '' ?>">
                            <td>
                                <a href="<?= e(url('gestionnaire/commande', ['id' => (int) $c['id']])) ?>" class="fw-semibold">
                                    <?= e($c['reference']) ?>
                                </a>
                                <?php if (!empty($c['commentaire'])): ?>
                                    <i class="bi bi-chat-left-text text-muted ms-1" title="<?= e($c['commentaire']) ?>"></i>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="text-small fw-semibold"><?= e($c['nom_boulangerie']) ?></div>
                                <div class="text-small text-muted"><?= e($c['boulanger_nom']) ?></div>
                            </td>
                            <td class="d-none d-lg-table-cell text-small text-muted"><?= e(date_fr($c['date_commande'], 'd/m H\hi')) ?></td>
                            <td class="d-none d-md-table-cell text-small">
                                <?php if (!empty($c['date_livraison'])): ?>
                                    <i class="bi bi-calendar-check text-success me-1"></i><?= e(date_fr($c['date_livraison'])) ?>
                                    <?php if (!empty($c['livreur'])): ?>
                                        <div class="text-muted"><?= e($c['livreur']) ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">souhaitée <?= e(date_fr($c['date_livraison_souhaitee'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end nma-num"><?= (int) $c['total_sacs'] ?></td>
                            <td><?= statut_badge($c['statut']) ?></td>
                            <td class="text-end">
                                <a href="<?= e(url('gestionnaire/commande', ['id' => (int) $c['id']])) ?>"
                                   class="btn btn-sm <?= $c['statut'] === 'recue' ? 'btn-nma' : 'btn-outline-nma' ?>">
                                    <?= $c['statut'] === 'recue' ? 'Valider' : 'Ouvrir' ?>
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
