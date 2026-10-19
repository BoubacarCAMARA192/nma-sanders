<?php
/**
 * F09 / F14 : centre de notifications de l'utilisateur connecte.
 * @var array $notifications
 */
$icones = ['info' => 'info-circle', 'succes' => 'check2-circle', 'alerte' => 'exclamation-triangle', 'danger' => 'x-octagon'];
$couleurs = ['info' => 'info', 'succes' => 'success', 'alerte' => 'warning', 'danger' => 'danger'];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-0">Notifications</h1>
        <p class="text-muted small mb-0">Chaque changement de statut de commande vous est signalé ici.</p>
    </div>
    <form method="post" action="<?= e(url('notifications/tout-lire')) ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-outline-nma">
            <i class="bi bi-check2-all me-1"></i>Tout marquer comme lu
        </button>
    </form>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if ($notifications === []): ?>
            <div class="nma-empty"><i class="bi bi-bell-slash"></i>Aucune notification pour le moment.</div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($notifications as $n): ?>
                    <a class="list-group-item list-group-item-action d-flex gap-3 py-3 <?= (int) $n['lu'] === 0 ? 'bg-nma-soft' : '' ?>"
                       href="<?= e(url('notifications/ouvrir', ['id' => (int) $n['id']])) ?>">
                        <i class="bi bi-<?= e($icones[$n['type']] ?? 'info-circle') ?> text-<?= e($couleurs[$n['type']] ?? 'info') ?> fs-4 mt-1"></i>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between gap-2">
                                <span class="fw-semibold"><?= e($n['titre']) ?></span>
                                <span class="text-small text-muted text-nowrap"><?= e(date_fr($n['cree_le'], 'd/m H\hi')) ?></span>
                            </div>
                            <div class="text-small text-muted"><?= e($n['message']) ?></div>
                        </div>
                        <?php if ((int) $n['lu'] === 0): ?>
                            <span class="badge text-bg-primary align-self-center">Nouveau</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
