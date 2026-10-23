<?php
/**
 * Page d'erreur generique.
 * @var int    $code
 * @var string $titre
 * @var string $message
 */
?>
<div class="d-flex align-items-center justify-content-center" style="min-height:60vh">
    <div class="text-center">
        <div class="display-1 fw-bold text-sc"><?= (int) $code ?></div>
        <h1 class="h4 fw-bold mb-2"><?= e($titre) ?></h1>
        <p class="text-muted"><?= e($message) ?></p>
        <a href="<?= e(url()) ?>" class="btn btn-sc">
            <i class="bi bi-house-door me-2"></i>Revenir à l'accueil
        </a>
    </div>
</div>
