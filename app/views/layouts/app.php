<?php
/**
 * Gabarit de l'espace connecte : navigation adaptee au role.
 * @var string $contenu
 * @var array  $utilisateur
 * @var array  $notifications
 * @var int    $notifNonLues
 * @var string $routeCourante
 */
$role        = $utilisateur['role'] ?? 'boulanger';
$titrePage   = $titre ?? APP_NAME;
$routeActive = $routeCourante ?? '';

/** Menu principal selon le role (navigation en 3 clics maximum). */
$menus = [
    'boulanger' => [
        ['tableau de bord',  'boulanger/tableau-de-bord',   'speedometer2',   "Tableau de bord"],
        ['catalogue',        'boulanger/catalogue',         'grid-3x3-gap',   'Catalogue'],
        ['nouvelle commande','boulanger/commande/nouvelle',  'plus-circle',    'Nouvelle commande'],
        ['historique',       'boulanger/historique',        'clock-history',  'Mes commandes'],
        ['profil',           'boulanger/profil',            'person-gear',    'Mon profil'],
    ],
    'gestionnaire' => [
        ['tableau de bord',  'gestionnaire/tableau-de-bord', 'speedometer2',  "Tableau de bord"],
        ['commandes',        'gestionnaire/commandes',       'inboxes',       'Commandes'],
        ['livraisons',       'gestionnaire/livraisons',      'truck',         'Livraisons'],
        ['clients',          'gestionnaire/boulangers',      'shop',          'Mes clients'],
        ['statistiques',     'gestionnaire/statistiques',    'bar-chart',     'Statistiques'],
    ],
    'administrateur' => [
        ['tableau de bord',  'administrateur/tableau-de-bord', 'speedometer2', 'Tableau de bord'],
        ['utilisateurs',     'administrateur/utilisateurs',    'people',       'Utilisateurs'],
        ['catalogue',        'administrateur/catalogue',       'box-seam',     'Catalogue'],
        ['statistiques',     'administrateur/statistiques',    'bar-chart',    'Statistiques'],
        ['journal',          'administrateur/journal',         'journal-text', 'Journal'],
        ['parametres',       'administrateur/parametres',      'sliders',      'Paramètres'],
    ],
];
$menu = $menus[$role] ?? [];

$estActif = static function (string $route) use ($routeActive): bool {
    return $routeActive === $route;
};

$roleLibelle = match ($role) {
    'boulanger'      => 'Boulanger',
    'gestionnaire'   => 'Gestionnaire',
    'administrateur' => 'Administrateur',
    default          => 'Utilisateur',
};
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#007e48">
    <title><?= e($titrePage) ?> — <?= e(APP_NAME) ?></title>
    <link rel="icon" href="<?= e(asset('assets/img/favicon.png')) ?>" type="image/png">
    <link rel="apple-touch-icon" href="<?= e(asset('assets/img/apple-touch-icon.png')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(asset('assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body class="sc-app role-<?= e($role) ?>">

<nav class="navbar sc-navbar sticky-top">
    <div class="container-fluid">
        <button class="btn btn-link text-white d-lg-none px-1" type="button" data-bs-toggle="offcanvas"
                data-bs-target="#menuLateral" aria-label="Ouvrir le menu">
            <i class="bi bi-list fs-3"></i>
        </button>

        <a class="navbar-brand d-flex align-items-center text-white fw-bold" href="<?= e(url(Core\Auth::routeAccueil())) ?>">
            <img src="<?= e(asset('assets/img/logo-sc-clair.png')) ?>" alt="<?= e(APP_NAME) ?>" class="sc-logo-nav me-2" width="34" height="34">
            <span><?= e(APP_NAME) ?></span>
        </a>

        <div class="d-flex align-items-center gap-1 ms-auto">
            <a class="btn btn-link text-white position-relative px-2" href="<?= e(url('notifications')) ?>"
               aria-label="Notifications">
                <i class="bi bi-bell fs-5"></i>
                <?php if (($notifNonLues ?? 0) > 0): ?>
                    <span class="position-absolute badge rounded-pill text-bg-danger sc-notif-badge">
                        <?= (int) $notifNonLues ?>
                    </span>
                <?php endif; ?>
            </a>

            <div class="dropdown">
                <button class="btn btn-link text-white dropdown-toggle px-2" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                    <i class="bi bi-person-circle fs-5"></i>
                    <span class="d-none d-md-inline ms-1"><?= e(mb_substr((string) $utilisateur['nom'], 0, 18)) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li class="px-3 py-2">
                        <div class="fw-semibold"><?= e($utilisateur['nom']) ?></div>
                        <div class="small text-muted"><?= e($roleLibelle) ?></div>
                        <div class="small text-muted"><?= e($utilisateur['email']) ?></div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <?php if ($role === 'boulanger'): ?>
                        <li><a class="dropdown-item" href="<?= e(url('boulanger/profil')) ?>"><i class="bi bi-person-gear me-2"></i>Mon profil</a></li>
                    <?php endif; ?>
                    <li><a class="dropdown-item" href="<?= e(url('notifications')) ?>"><i class="bi bi-bell me-2"></i>Notifications</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= e(logout_url()) ?>"><i class="bi bi-box-arrow-right me-2"></i>Se déconnecter</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <!-- Menu lateral (bureau) -->
        <aside class="col-lg-2 d-none d-lg-block sc-sidebar p-0">
            <nav class="nav flex-column py-3">
                <?php foreach ($menu as [$cle, $route, $icone, $libelle]): ?>
                    <a class="nav-link sc-nav-link <?= $estActif($route) ? 'active' : '' ?>" href="<?= e(url($route)) ?>">
                        <i class="bi bi-<?= e($icone) ?>"></i>
                        <span><?= e($libelle) ?></span>
                    </a>
                <?php endforeach; ?>

                <hr class="mx-3 my-2">
                <a class="nav-link sc-nav-link" href="<?= e(logout_url()) ?>">
                    <i class="bi bi-box-arrow-right"></i><span>Déconnexion</span>
                </a>
            </nav>

            <?php if ($role === 'boulanger'): ?>
                <div class="px-3 pb-3">
                    <div class="sc-help-card">
                        <i class="bi bi-telephone-outbound fs-4"></i>
                        <div class="small fw-semibold mt-1">Besoin d'aide ?</div>
                        <div class="small text-muted">Service commercial Sanders Connect</div>
                        <a class="small fw-semibold" href="tel:+221771249186">+221 77 124 91 86</a>
                    </div>
                </div>
            <?php endif; ?>
        </aside>

        <!-- Contenu -->
        <main class="col-lg-10 sc-content pb-5">
            <?php foreach (($messages ?? []) as $msg): ?>
                <div class="alert alert-<?= e($msg['type']) ?> alert-dismissible fade show shadow-sm d-flex" role="alert">
                    <i class="bi bi-<?= $msg['type'] === 'success' ? 'check2-circle' : ($msg['type'] === 'danger' ? 'exclamation-triangle' : 'info-circle') ?> me-2 mt-1"></i>
                    <div class="flex-grow-1"><?= e($msg['message']) ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            <?php endforeach; ?>

            <?= $contenu ?>
        </main>
    </div>
</div>

<!-- Menu mobile -->
<div class="offcanvas offcanvas-start sc-offcanvas" tabindex="-1" id="menuLateral" aria-labelledby="menuLateralLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title text-white d-flex align-items-center" id="menuLateralLabel">
            <img src="<?= e(asset('assets/img/logo-sc-clair.png')) ?>" alt="<?= e(APP_NAME) ?>" class="sc-logo-nav me-2" width="34" height="34">
            <?= e(APP_NAME) ?>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Fermer"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="px-3 py-3 border-bottom">
            <div class="fw-semibold"><?= e($utilisateur['nom']) ?></div>
            <div class="small text-muted"><?= e($roleLibelle) ?></div>
        </div>
        <nav class="nav flex-column py-2">
            <?php foreach ($menu as [$cle, $route, $icone, $libelle]): ?>
                <a class="nav-link sc-nav-link <?= $estActif($route) ? 'active' : '' ?>" href="<?= e(url($route)) ?>">
                    <i class="bi bi-<?= e($icone) ?>"></i><span><?= e($libelle) ?></span>
                </a>
            <?php endforeach; ?>
            <a class="nav-link sc-nav-link" href="<?= e(url('notifications')) ?>">
                <i class="bi bi-bell"></i><span>Notifications</span>
                <?php if (($notifNonLues ?? 0) > 0): ?>
                    <span class="badge text-bg-danger ms-auto"><?= (int) $notifNonLues ?></span>
                <?php endif; ?>
            </a>
            <hr class="mx-3 my-2">
            <a class="nav-link sc-nav-link text-danger" href="<?= e(logout_url()) ?>">
                <i class="bi bi-box-arrow-right"></i><span>Déconnexion</span>
            </a>
        </nav>
    </div>
</div>

<!-- Barre de navigation mobile -->
<nav class="sc-bottom-nav d-lg-none">
    <?php foreach (array_slice($menu, 0, 4) as [$cle, $route, $icone, $libelle]): ?>
        <a href="<?= e(url($route)) ?>" class="<?= $estActif($route) ? 'active' : '' ?>">
            <i class="bi bi-<?= e($icone) ?>"></i>
            <span><?= e($libelle === 'Nouvelle commande' ? 'Commander' : ($libelle === 'Tableau de bord' ? 'Accueil' : ($libelle === 'Mes commandes' ? 'Commandes' : $libelle))) ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="<?= e(asset('assets/js/app.js')) ?>"></script>
</body>
</html>
