<?php
/**
 * Acces refuse (controle d'acces par role).
 */
$utilisateur = Core\Auth::utilisateur();
?>
<div class="d-flex align-items-center justify-content-center" style="min-height:60vh">
    <div class="text-center px-3">
        <div class="display-1 fw-bold text-danger">403</div>
        <h1 class="h4 fw-bold mb-2">Accès non autorisé</h1>
        <p class="text-muted">
            Votre profil<?= $utilisateur !== null ? ' (' . e($utilisateur['role']) . ')' : '' ?> ne permet pas
            d'accéder à cette page. Cette tentative a été enregistrée dans le journal des opérations.
        </p>
        <a href="<?= e(url(Core\Auth::connecte() ? Core\Auth::routeAccueil() : 'connexion')) ?>" class="btn btn-nma">
            <i class="bi bi-arrow-left me-2"></i>Retour à mon espace
        </a>
    </div>
</div>
