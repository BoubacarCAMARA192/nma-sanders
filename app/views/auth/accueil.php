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

<div class="nma-univers mt-4 nma-reveal" style="--retard:.22s" aria-label="Les trois univers de la plateforme">

    <div class="nma-univers-carte nma-tilt">
        <svg viewBox="0 0 90 48" aria-hidden="true">
            <path d="M18 34 C 24 20 34 15 45 15 C 56 15 66 20 72 34 C 62 30 54 28 45 28 C 36 28 28 30 18 34 Z"
                  fill="#e9b45c" stroke="#c98f2e" stroke-width="1.6"/>
            <path d="M30 29 C 31 24 35 21 39 20" stroke="#c98f2e" stroke-width="1.4" fill="none"/>
            <path d="M60 29 C 59 24 55 21 51 20" stroke="#c98f2e" stroke-width="1.4" fill="none"/>
            <g class="vapeur" stroke="#d9c39a" stroke-width="1.6" fill="none" stroke-linecap="round">
                <path d="M38 12 C 36 9 40 7 38 4"/>
                <path d="M46 11 C 44 8 48 6 46 3"/>
                <path d="M54 12 C 52 9 56 7 54 4"/>
            </g>
            <line x1="14" y1="40" x2="76" y2="40" stroke="#e8ddc9" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <strong>Fournil</strong>
        <small>Farine prête à pétrir, sacs de 50 kg.</small>
    </div>

    <div class="nma-univers-carte nma-tilt">
        <svg viewBox="0 0 90 48" aria-hidden="true">
            <g class="u-camion">
                <rect x="10" y="14" width="42" height="20" rx="4" fill="#ffffff" stroke="#d9c39a" stroke-width="1.6"/>
                <rect x="14" y="18" width="34" height="12" rx="2" fill="#fbf0da"/>
                <text x="31" y="27" text-anchor="middle" font-size="7.5" font-weight="700" fill="#a97c22">NMA</text>
                <rect x="52" y="20" width="16" height="14" rx="3" fill="#8b5a06"/>
                <rect x="54" y="22" width="11" height="6" rx="2" fill="#cfe8ff"/>
                <circle cx="22" cy="36" r="5.6" fill="#3b2b17"/>
                <circle cx="22" cy="36" r="2" fill="#d8b26a"/>
                <circle cx="60" cy="36" r="5.6" fill="#3b2b17"/>
                <circle cx="60" cy="36" r="2" fill="#d8b26a"/>
            </g>
            <line x1="8" y1="44" x2="82" y2="44" stroke="#e8ddc9" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <strong>Livraison</strong>
        <small>Le camion NMA jusqu'à votre boutique.</small>
    </div>

    <div class="nma-univers-carte nma-tilt">
        <svg viewBox="0 0 90 48" aria-hidden="true">
            <polyline points="12,38 28,30 42,33 58,22 76,12" fill="none" stroke="#e8a33d"
                      stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="28" cy="30" r="2.6" fill="#8b5a06"/>
            <circle cx="58" cy="22" r="2.6" fill="#8b5a06"/>
            <circle class="point" cx="76" cy="12" r="3.4" fill="#e8a33d"/>
            <line x1="12" y1="42" x2="78" y2="42" stroke="#e8ddc9" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <strong>Suivi</strong>
        <small>Statuts horodatés, historique complet.</small>
    </div>

</div>

<div class="nma-chiffres mt-3 nma-reveal" style="--retard:.3s">
    <div><span class="nma-stat-value">8</span><small>produits au catalogue</small></div>
    <div><span class="nma-stat-value">24</span><small>h de délai moyen</small></div>
    <div><span class="nma-stat-value">5</span><small>étapes de suivi</small></div>
</div>

<hr class="my-4">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 small text-muted">
    <span><i class="bi bi-shield-lock me-1"></i>Accès sécurisé par rôle</span>
    <a href="<?= e(url('mot-de-passe-oublie')) ?>" class="link-secondary">Mot de passe oublié ?</a>
</div>
