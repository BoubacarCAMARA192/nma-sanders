<?php
/**
 * Page d'accueil publique : l'utilisateur choisit d'abord entre
 * créer un compte (demande d'inscription) ou se connecter.
 */
?>
<div class="text-center mb-4">
    <span class="nma-brand-badge mb-2" style="width:46px;height:46px;font-size:1.45rem;">
        <i class="bi bi-basket2-fill"></i>
    </span>
    <h2 class="h4 fw-bold mb-1">Bienvenue</h2>
    <p class="text-muted small mb-0">Première visite ou déjà client ? Choisissez votre parcours.</p>
</div>

<div class="d-grid gap-3">

    <a href="<?= e(url('inscription')) ?>" class="nma-choix nma-choix-principal nma-reveal" style="--retard:.05s">
        <span class="nma-choix-ico"><i class="bi bi-shop"></i></span>
        <span class="nma-choix-txt">
            <strong>Créer un compte</strong>
            <small>Nouveau client : demandez votre accès en 1 minute. Notre équipe vous active après contact.</small>
        </span>
        <i class="bi bi-arrow-right nma-choix-fleche"></i>
    </a>

    <a href="<?= e(url('connexion')) ?>" class="nma-choix nma-reveal" style="--retard:.14s">
        <span class="nma-choix-ico"><i class="bi bi-box-arrow-in-right"></i></span>
        <span class="nma-choix-txt">
            <strong>Se connecter</strong>
            <small>Vous avez déjà un compte boulanger, gestionnaire ou administrateur.</small>
        </span>
        <i class="bi bi-arrow-right nma-choix-fleche"></i>
    </a>

</div>

<div class="nma-etapes mt-4 nma-reveal" style="--retard:.22s">
    <div class="nma-etape">
        <span class="num">1</span>
        <div><strong>Demande en ligne</strong><small>Renseignez votre établissement et vos coordonnées.</small></div>
    </div>
    <div class="nma-etape">
        <span class="num">2</span>
        <div><strong>Appel commercial</strong><small>Nous vérifions ensemble votre zone de livraison.</small></div>
    </div>
    <div class="nma-etape">
        <span class="num">3</span>
        <div><strong>Commandez !</strong><small>Votre compte est activé, le catalogue vous attend.</small></div>
    </div>
</div>

<hr class="my-4">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 small text-muted">
    <span><i class="bi bi-shield-lock me-1"></i>Accès sécurisé par rôle</span>
    <a href="<?= e(url('mot-de-passe-oublie')) ?>" class="link-secondary">Mot de passe oublié ?</a>
</div>
