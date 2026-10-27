<?php
/**
 * Page d'accueil publique : le visiteur choisit entre créer un compte ou se connecter.
 */
?>
<div class="text-center mb-4">
    <span class="sc-brand-badge mb-3 sc-logo-glow" style="width:64px;height:64px;font-size:1.9rem;border-radius:18px;">
        <i class="bi bi-basket2-fill"></i>
    </span>
    <h2 class="h4 fw-bold mb-1"><?= e(APP_NAME) ?></h2>
    <p class="text-muted small mb-0">Commandez votre farine, suivez votre livraison.</p>
</div>

<div class="d-grid gap-3">

    <a href="<?= e(url('inscription')) ?>" class="sc-choix sc-choix-principal sc-reveal" style="--retard:.05s">
        <span class="sc-choix-ico"><i class="bi bi-shop"></i></span>
        <span class="sc-choix-txt">
            <strong>Créer un compte</strong>
            <small>Première commande ? Demandez votre accès.</small>
        </span>
        <i class="bi bi-arrow-right sc-choix-fleche"></i>
    </a>

    <a href="<?= e(url('connexion')) ?>" class="sc-choix sc-reveal" style="--retard:.14s">
        <span class="sc-choix-ico"><i class="bi bi-box-arrow-in-right"></i></span>
        <span class="sc-choix-txt">
            <strong>Se connecter</strong>
            <small>J'ai déjà un compte.</small>
        </span>
        <i class="bi bi-arrow-right sc-choix-fleche"></i>
    </a>

</div>

<div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
    <span class="sc-pill"><i class="bi bi-basket2 me-1"></i>Farines Sanders</span>
    <span class="sc-pill"><i class="bi bi-truck me-1"></i>Suivi livraison</span>
    <span class="sc-pill"><i class="bi bi-shield-lock me-1"></i>Accès sécurisé</span>
</div>

<div class="text-center mt-4 small">
    <a href="<?= e(url('mot-de-passe-oublie')) ?>" class="link-secondary">Mot de passe oublié ?</a>
</div>
