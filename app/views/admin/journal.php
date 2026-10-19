<?php
/**
 * Journal des operations : tracabilite complete (identifiant unique par operation).
 * @var array  $operations
 * @var array  $actions
 * @var string $action
 * @var string $q
 * @var int    $total
 */
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-0">Journal des opÃ©rations</h1>
        <p class="text-muted small mb-0">
            <?= (int) $total ?> opÃ©ration(s) enregistrÃ©e(s) depuis la mise en service Â·
            <?= count($operations) ?> affichÃ©e(s)
        </p>
    </div>
</div>

<form method="get" action="<?= e(url('administrateur/journal')) ?>" class="card mb-3">
    <input type="hidden" name="r" value="administrateur/journal">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-4">
                <label class="form-label small" for="action">Type d'opÃ©ration</label>
                <select class="form-select" id="action" name="action" data-filtre-auto>
                    <option value="">Toutes les opÃ©rations</option>
                    <?php foreach ($actions as $a): ?>
                        <option value="<?= e($a) ?>" <?= $action === $a ? 'selected' : '' ?>><?= e(action_fr($a)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-5">
                <label class="form-label small" for="q">Recherche</label>
                <input type="search" class="form-control" id="q" name="q" value="<?= e($q) ?>"
                       placeholder="Auteur, dÃ©tail de l'opÃ©ration...">
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-nma flex-grow-1"><i class="bi bi-search"></i></button>
                <a href="<?= e(url('administrateur/journal')) ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <?php if ($operations === []): ?>
            <div class="nma-empty"><i class="bi bi-journal"></i>Aucune opÃ©ration ne correspond Ã  ces critÃ¨res.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:80px">NÂ°</th>
                            <th style="width:170px">Date et heure</th>
                            <th>Auteur</th>
                            <th>OpÃ©ration</th>
                            <th class="d-none d-lg-table-cell">DÃ©tail</th>
                            <th class="d-none d-md-table-cell" style="width:120px">Adresse IP</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($operations as $op): ?>
                        <tr>
                            <td class="nma-num text-muted text-small"><?= (int) $op['id'] ?></td>
                            <td class="text-small nma-num"><?= e(date_fr($op['date_heure'], 'd/m/Y H:i:s')) ?></td>
                            <td class="text-small">
                                <?= e(Core\Journal::auteur($op['auteur'] ?? null, $op['auteur_role'] ?? null)) ?>
                            </td>
                            <td>
                                <span class="badge text-bg-light border"><?= e(action_fr((string) $op['action'])) ?></span>
                                <?php if (!empty($op['entite'])): ?>
                                    <div class="text-small text-muted"><?= e($op['entite']) ?><?= !empty($op['entite_id']) ? ' #' . (int) $op['entite_id'] : '' ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="d-none d-lg-table-cell text-small text-muted"><?= e($op['details'] ?? '') ?></td>
                            <td class="d-none d-md-table-cell text-small text-muted nma-num"><?= e($op['adresse_ip'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

