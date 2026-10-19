<?php
/**
 * Gabarit public : page d'accueil / connexion, mot de passe oublie.
 * @var string $contenu
 * @var string $titre
 */
$titrePage = $titre ?? APP_NAME;
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#7c4a03">
    <meta name="description" content="Plateforme NMA Sanders : commande de farine en autonomie et suivi des livraisons en temps réel.">
    <title><?= e($titrePage) ?></title>
    <link rel="icon" href="<?= e(asset('assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(asset('assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body class="nma-public">

<div class="nma-blob b1" aria-hidden="true"></div>
<div class="nma-blob b2" aria-hidden="true"></div>
<div class="nma-blob b3" aria-hidden="true"></div>

<main class="nma-auth-wrapper">
    <div class="container py-4">
        <div class="row justify-content-center align-items-center min-vh-100 g-4 g-lg-5">

            <div class="col-12 col-lg-6 col-xl-5 text-center text-lg-start nma-auth-brand">
                <div class="nma-logo nma-logo-glow mx-auto mx-lg-0 mb-3 nma-reveal">
                    <i class="bi bi-basket2-fill"></i>
                </div>
                <h1 class="display-6 fw-bold text-white mb-2 nma-reveal" style="--retard:.06s"><?= e(APP_NAME) ?></h1>
                <p class="lead text-white-50 mb-4 nma-reveal" style="--retard:.12s"><?= e(APP_SLOGAN) ?></p>

                <div class="nma-scene-card nma-tilt nma-reveal mb-4" style="--retard:.18s">
                    <div class="nma-scene-flottant">
                        <svg class="nma-scene" viewBox="0 0 640 300" role="img"
                             aria-label="Illustration animée : champ de blé doré, soleil, sacs de farine NMA et camion de livraison">
                            <defs>
                                <linearGradient id="nmaCiel" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0" stop-color="#ffe1a3"/>
                                    <stop offset=".65" stop-color="#f2b455"/>
                                    <stop offset="1" stop-color="#dd9430"/>
                                </linearGradient>
                                <linearGradient id="nmaSol" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0" stop-color="#d99a34"/>
                                    <stop offset="1" stop-color="#8b5a06"/>
                                </linearGradient>
                                <g id="nmaEpi">
                                    <path d="M0 0 C -1 -14 -1 -28 0 -44" stroke="#7a5410" stroke-width="2.4" fill="none"/>
                                    <ellipse cx="-5" cy="-16" rx="5" ry="8" fill="#f7e3b6" transform="rotate(-24 -5 -16)"/>
                                    <ellipse cx="5" cy="-16" rx="5" ry="8" fill="#f2d492" transform="rotate(24 5 -16)"/>
                                    <ellipse cx="-5" cy="-28" rx="5" ry="8" fill="#f7e3b6" transform="rotate(-24 -5 -28)"/>
                                    <ellipse cx="5" cy="-28" rx="5" ry="8" fill="#f2d492" transform="rotate(24 5 -28)"/>
                                    <ellipse cx="0" cy="-40" rx="4.4" ry="7" fill="#fbeccb"/>
                                    <ellipse cx="0" cy="-50" rx="3.4" ry="5.5" fill="#f7e3b6"/>
                                </g>
                            </defs>
                            <rect width="640" height="300" fill="url(#nmaCiel)"/>
                            <g transform="translate(540 74)">
                                <g class="nma-soleil-rayons" stroke="#ffdf9e" stroke-width="4" stroke-linecap="round">
                                    <line x1="0" y1="-44" x2="0" y2="-58"/>
                                    <line x1="0" y1="44" x2="0" y2="58"/>
                                    <line x1="-44" y1="0" x2="-58" y2="0"/>
                                    <line x1="44" y1="0" x2="58" y2="0"/>
                                    <line x1="-31" y1="-31" x2="-41" y2="-41"/>
                                    <line x1="31" y1="31" x2="41" y2="41"/>
                                    <line x1="-31" y1="31" x2="-41" y2="41"/>
                                    <line x1="31" y1="-31" x2="41" y2="-41"/>
                                </g>
                                <circle r="30" fill="#ffe3a9"/>
                                <circle class="nma-soleil-halo" r="30" fill="none" stroke="#fff3d6" stroke-width="3"/>
                            </g>
                            <g transform="translate(120 66)">
                                <g class="nma-nuage n1" fill="#fff6e5" opacity=".9">
                                    <ellipse rx="40" ry="14"/>
                                    <ellipse cx="-26" cy="6" rx="26" ry="10"/>
                                    <ellipse cx="26" cy="6" rx="30" ry="11"/>
                                </g>
                            </g>
                            <g transform="translate(300 40) scale(.8)">
                                <g class="nma-nuage n2" fill="#ffffff" opacity=".75">
                                    <ellipse rx="40" ry="14"/>
                                    <ellipse cx="-26" cy="6" rx="26" ry="10"/>
                                    <ellipse cx="26" cy="6" rx="30" ry="11"/>
                                </g>
                            </g>
                            <g transform="translate(430 96) scale(.65)">
                                <g class="nma-nuage n3" fill="#fff6e5" opacity=".6">
                                    <ellipse rx="40" ry="14"/>
                                    <ellipse cx="-26" cy="6" rx="26" ry="10"/>
                                    <ellipse cx="26" cy="6" rx="30" ry="11"/>
                                </g>
                            </g>
                            <path d="M0 210 C 120 186 220 196 320 206 C 440 218 540 200 640 208 L640 300 L0 300 Z" fill="url(#nmaSol)"/>
                            <g opacity=".75">
                                <g transform="translate(90 252) scale(.78)"><use href="#nmaEpi" class="epi" style="animation-delay:-.35s"/></g>
                                <g transform="translate(160 244) scale(.72)"><use href="#nmaEpi" class="epi" style="animation-delay:-1.2s"/></g>
                                <g transform="translate(250 250) scale(.8)"><use href="#nmaEpi" class="epi" style="animation-delay:-2s"/></g>
                                <g transform="translate(330 242) scale(.7)"><use href="#nmaEpi" class="epi" style="animation-delay:-.8s"/></g>
                                <g transform="translate(420 248) scale(.76)"><use href="#nmaEpi" class="epi" style="animation-delay:-1.6s"/></g>
                            </g>
                            <g>
                                <g transform="translate(62 272) scale(1.08)"><use href="#nmaEpi" class="epi" style="animation-delay:-.2s"/></g>
                                <g transform="translate(130 264) scale(.98)"><use href="#nmaEpi" class="epi" style="animation-delay:-1.4s"/></g>
                                <g transform="translate(200 274) scale(1.12)"><use href="#nmaEpi" class="epi" style="animation-delay:-2.4s"/></g>
                                <g transform="translate(275 266) scale(1)"><use href="#nmaEpi" class="epi" style="animation-delay:-.7s"/></g>
                                <g transform="translate(345 276) scale(1.15)"><use href="#nmaEpi" class="epi" style="animation-delay:-1.9s"/></g>
                                <g transform="translate(415 268) scale(1.02)"><use href="#nmaEpi" class="epi" style="animation-delay:-2.8s"/></g>
                                <g transform="translate(480 276) scale(1.1)"><use href="#nmaEpi" class="epi" style="animation-delay:-1.1s"/></g>
                                <g transform="translate(596 270) scale(1.05)"><use href="#nmaEpi" class="epi" style="animation-delay:-2.2s"/></g>
                            </g>
                            <g transform="translate(470 188)">
                                <path d="M0 22 C 4 10 8 6 8 0 L40 0 C 40 6 44 10 48 22 L48 58 Q48 66 40 66 L8 66 Q0 66 0 58 Z"
                                      fill="#f6ead2" stroke="#dcc49a" stroke-width="2"/>
                                <rect x="8" y="-4" width="32" height="8" rx="4" fill="#e8d5ae"/>
                                <text x="24" y="42" text-anchor="middle" font-size="13" font-weight="700" fill="#a97c22" font-family="'Segoe UI', sans-serif">NMA</text>
                                <g transform="translate(34 26) scale(.78)">
                                    <path d="M0 22 C 4 10 8 6 8 0 L40 0 C 40 6 44 10 48 22 L48 58 Q48 66 40 66 L8 66 Q0 66 0 58 Z"
                                          fill="#fbf3e2" stroke="#dcc49a" stroke-width="2"/>
                                    <rect x="8" y="-4" width="32" height="8" rx="4" fill="#e8d5ae"/>
                                </g>
                            </g>
                            <g transform="translate(0 236)">
                                <g class="nma-camion">
                                    <rect x="0" y="0" width="58" height="30" rx="5" fill="#ffffff"/>
                                    <rect x="4" y="4" width="50" height="22" rx="3" fill="#fbf0da"/>
                                    <text x="29" y="19" text-anchor="middle" font-size="11" font-weight="700" fill="#a97c22" font-family="'Segoe UI', sans-serif">NMA</text>
                                    <rect x="58" y="8" width="20" height="22" rx="4" fill="#6d4504"/>
                                    <rect x="61" y="12" width="13" height="9" rx="2" fill="#cfe8ff"/>
                                    <circle cx="14" cy="34" r="7" fill="#3b2b17"/>
                                    <circle cx="14" cy="34" r="2.6" fill="#d8b26a"/>
                                    <circle cx="66" cy="34" r="7" fill="#3b2b17"/>
                                    <circle cx="66" cy="34" r="2.6" fill="#d8b26a"/>
                                </g>
                            </g>
                            <g fill="#fff8ea">
                                <circle class="nma-poussiere p1" cx="120" cy="192" r="2.6"/>
                                <circle class="nma-poussiere p2" cx="232" cy="172" r="2.2"/>
                                <circle class="nma-poussiere p3" cx="332" cy="206" r="2.8"/>
                                <circle class="nma-poussiere p4" cx="432" cy="162" r="2.2"/>
                            </g>
                        </svg>
                    </div>
                    <div class="nma-marquee" aria-hidden="true">
                        <div class="nma-marquee-piste">
                            <span class="nma-marquee-moitie">
                                <span>Farine T55</span><span class="pt">•</span>
                                <span>Sacs de 50 kg</span><span class="pt">•</span>
                                <span>Livraison Dakar &amp; régions</span><span class="pt">•</span>
                                <span>Commande en 3 clics</span><span class="pt">•</span>
                                <span>Suivi en direct</span><span class="pt">•</span>
                            </span>
                            <span class="nma-marquee-moitie">
                                <span>Farine T55</span><span class="pt">•</span>
                                <span>Sacs de 50 kg</span><span class="pt">•</span>
                                <span>Livraison Dakar &amp; régions</span><span class="pt">•</span>
                                <span>Commande en 3 clics</span><span class="pt">•</span>
                                <span>Suivi en direct</span><span class="pt">•</span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 mb-4">
                    <div class="nma-feature text-start nma-reveal" style="--retard:.26s">
                        <div class="puce"><i class="bi bi-bag-plus"></i></div>
                        <div>
                            <div class="text-white fw-semibold small">Commandez sans appeler votre commercial</div>
                            <div class="text-white-50 small">Catalogue à jour, panier et réapprovisionnement express.</div>
                        </div>
                    </div>
                    <div class="nma-feature text-start nma-reveal" style="--retard:.32s">
                        <div class="puce"><i class="bi bi-geo-alt"></i></div>
                        <div>
                            <div class="text-white fw-semibold small">Suivez chaque étape jusqu'à la livraison</div>
                            <div class="text-white-50 small">Statuts horodatés : reçue, validée, préparation, livrée.</div>
                        </div>
                    </div>
                    <div class="nma-feature text-start nma-reveal" style="--retard:.38s">
                        <div class="puce"><i class="bi bi-clock-history"></i></div>
                        <div>
                            <div class="text-white fw-semibold small">Retrouvez tout votre historique en un clic</div>
                            <div class="text-white-50 small">Quantités, montants et dates de livraison conservés.</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-lg-start nma-reveal" style="--retard:.42s">
                    <span class="nma-badge-confiance"><i class="bi bi-shield-check"></i> Accès sécurisé</span>
                    <span class="nma-badge-confiance"><i class="bi bi-phone"></i> Pensé pour le mobile</span>
                    <span class="nma-badge-confiance"><i class="bi bi-headset"></i> Service commercial local</span>
                </div>
            </div>

            <div class="col-12 col-lg-5 col-xl-4">
                <?php foreach (($messages ?? []) as $msg): ?>
                    <div class="alert alert-<?= e($msg['type']) ?> d-flex align-items-start shadow-sm" role="alert">
                        <i class="bi bi-<?= $msg['type'] === 'success' ? 'check2-circle' : ($msg['type'] === 'danger' ? 'exclamation-triangle' : 'info-circle') ?> me-2"></i>
                        <div><?= e($msg['message']) ?></div>
                    </div>
                <?php endforeach; ?>
                <div class="card nma-glass border-0 nma-reveal" style="--retard:.15s">
                    <div class="card-body p-4 p-md-5">
                        <?= $contenu ?>
                    </div>
                </div>
                <p class="text-center text-white-50 small mt-3 mb-0">
                    &copy; <?= date('Y') ?> <?= e(APP_NAME) ?> — Sénégal · Version <?= e(APP_VERSION) ?>
                </p>
            </div>

        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="<?= e(asset('assets/js/app.js')) ?>"></script>
</body>
</html>
