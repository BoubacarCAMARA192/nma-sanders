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
    <meta name="theme-color" content="#007e48">
    <meta name="description" content="Sanders Connect : commande de farine en ligne pour boulangers, avec suivi de livraison en temps réel.">
    <title><?= e($titrePage) ?></title>
    <link rel="icon" href="<?= e(asset('assets/img/favicon.png')) ?>" type="image/png">
    <link rel="apple-touch-icon" href="<?= e(asset('assets/img/apple-touch-icon.png')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(asset('assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body class="sc-public">

<main class="sc-auth-wrapper">
    <div class="container">
        <div class="sc-auth-shell">
            <div class="row g-0">

                <div class="col-12 col-lg-6 sc-auth-brand">
                    <div class="sc-auth-brand-inner">
                        <img src="<?= e(asset('assets/img/logo-sc-clair.png')) ?>" alt="<?= e(APP_NAME) ?>"
                             class="sc-logo-public" width="88" height="88">
                        <div class="sc-auth-eyebrow">Sanders Connect · Sénégal</div>
                        <h1 class="sc-auth-title">Votre farine,<br>livrée à la boulangerie.</h1>
                        <p class="sc-auth-text">
                            L'espace de commande des boulangers Sanders : catalogue,
                            suivi de livraison et paiement en ligne.
                        </p>
                        <ul class="sc-auth-points">
                            <li>
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Toute la gamme de farines Sanders au catalogue.</span>
                            </li>
                            <li>
                                <i class="bi bi-check-circle-fill"></i>
                                <span>De la commande à la livraison, un suivi en temps réel.</span>
                            </li>
                            <li>
                                <i class="bi bi-check-circle-fill"></i>
                                <span>Carte bancaire, Wave ou Orange Money.</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="col-12 col-lg-6 sc-auth-card-wrap">
                    <?php foreach (($messages ?? []) as $msg): ?>
                        <div class="alert alert-<?= e($msg['type']) ?> d-flex align-items-start shadow-sm" role="alert">
                            <i class="bi bi-<?= $msg['type'] === 'success' ? 'check2-circle' : ($msg['type'] === 'danger' ? 'exclamation-triangle' : 'info-circle') ?> me-2"></i>
                            <div><?= e($msg['message']) ?></div>
                        </div>
                    <?php endforeach; ?>

                    <div class="sc-auth-card">
                        <?= $contenu ?>
                    </div>

                    <p class="sc-auth-foot mb-0">
                        &copy; <?= date('Y') ?> <?= e(APP_NAME) ?> — Sénégal · Version <?= e(APP_VERSION) ?>
                    </p>
                </div>

            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="<?= e(asset('assets/js/app.js')) ?>"></script>
</body>
</html>
