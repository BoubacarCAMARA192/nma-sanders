<?php
/**
 * Page d'accueil publique : l'utilisateur choisit d'abord entre
 * créer un compte (demande d'inscription) ou se connecter.
 */
?>
<div class="text-center mb-4">
    <span class="sc-brand-badge mb-2" style="width:46px;height:46px;font-size:1.45rem;">
        <i class="bi bi-basket2-fill"></i>
    </span>
    <h2 class="h4 fw-bold mb-1">Bienvenue</h2>
    <p class="text-muted small mb-0">Accédez à votre espace Sanders Connect.</p>
</div>

<div class="d-grid gap-3">

    <a href="<?= e(url('inscription')) ?>" class="sc-choix sc-choix-principal sc-reveal" style="--retard:.05s">
        <span class="sc-choix-ico"><i class="bi bi-shop"></i></span>
        <span class="sc-choix-txt">
            <strong>Créer un compte</strong>
            <small>Nouveau client : demandez votre accès en 1 minute. Notre équipe vous active après contact.</small>
        </span>
        <i class="bi bi-arrow-right sc-choix-fleche"></i>
    </a>

    <a href="<?= e(url('connexion')) ?>" class="sc-choix sc-reveal" style="--retard:.14s">
        <span class="sc-choix-ico"><i class="bi bi-box-arrow-in-right"></i></span>
        <span class="sc-choix-txt">
            <strong>Se connecter</strong>
            <small>Vous avez déjà un compte boulanger, gestionnaire ou administrateur.</small>
        </span>
        <i class="bi bi-arrow-right sc-choix-fleche"></i>
    </a>

</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 small text-muted mt-4">
    <span><i class="bi bi-shield-lock me-1"></i>Accès sécurisé par rôle</span>
    <a href="<?= e(url('mot-de-passe-oublie')) ?>" class="link-secondary">Mot de passe oublié ?</a>
</div>
