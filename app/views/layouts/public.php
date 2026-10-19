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

                <div class="d-grid gap-2 mb-4">
                    <div class="nma-feature text-start nma-reveal" style="--retard:.18s">
                        <div class="puce"><i class="bi bi-bag-plus"></i></div>
                        <div>
                            <div class="text-white fw-semibold small">Commandez sans appeler votre commercial</div>
                            <div class="text-white-50 small">Catalogue à jour, panier et réapprovisionnement express.</div>
                        </div>
                    </div>
                    <div class="nma-feature text-start nma-reveal" style="--retard:.26s">
                        <div class="puce"><i class="bi bi-geo-alt"></i></div>
                        <div>
                            <div class="text-white fw-semibold small">Suivez chaque étape jusqu'à la livraison</div>
                            <div class="text-white-50 small">Statuts horodatés : reçue, validée, préparation, livrée.</div>
                        </div>
                    </div>
                    <div class="nma-feature text-start nma-reveal" style="--retard:.34s">
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
