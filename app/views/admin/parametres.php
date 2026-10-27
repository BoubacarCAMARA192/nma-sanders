<?php
/**
 * Parametres applicatifs (regles de gestion ajustables).
 * @var array $parametres
 */
$descriptions = [
    'delai_minimum_heures'        => "Délai minimum imposé au boulanger entre le dépôt de la commande et la date de livraison souhaitée.",
    'capacite_sacs_par_jour'      => "Charge maximale de livraison par jour, en sacs. Au-delà, la journée est signalée comme surchargée. Mettre 0 pour désactiver le contrôle.",
    'max_tentatives_connexion'    => "Nombre d'échecs de connexion avant blocage temporaire du compte.",
    'duree_blocage_minutes'       => "Durée du blocage du compte après échecs répétés.",
    'session_duree_minutes'       => "Déconnexion automatique après cette durée d'inactivité.",
    'jours_historique_par_defaut' => "Profondeur d'historique proposée par défaut dans les écrans de suivi.",
];
?>
<h1 class="h4 fw-bold mb-1">Paramètres de la plateforme</h1>
<p class="text-muted small mb-3">
    Ces valeurs pilotent les règles de gestion et la sécurité de la plateforme. Elles s'appliquent immédiatement.
</p>

<div class="row g-3">
    <div class="col-12 col-lg-8">
        <form method="post" action="<?= e(url('administrateur/parametres')) ?>" class="card">
            <?= csrf_field() ?>
            <div class="card-header"><i class="bi bi-sliders text-sc me-2"></i>Règles de gestion et sécurité</div>
            <div class="card-body">
                <?php foreach ($parametres as $cle => $valeur): ?>
                    <div class="mb-4">
                        <label class="form-label" for="<?= e($cle) ?>">
                            <?= e(parametre_fr($cle)) ?>
                        </label>
                        <input type="number" class="form-control" id="<?= e($cle) ?>" name="<?= e($cle) ?>"
                               value="<?= e($valeur) ?>" min="0" required inputmode="numeric"
                               <?= isset($descriptions[$cle]) ? '' : 'readonly' ?>>
                        <?php if (isset($descriptions[$cle])): ?>
                            <div class="form-text"><?= e($descriptions[$cle]) ?></div>
                        <?php else: ?>
                            <div class="form-text text-muted">Paramètre système non modifiable depuis l'interface.</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="card-footer bg-white">
                <button type="submit" class="btn btn-sc w-100">
                    <i class="bi bi-save me-2"></i>Enregistrer les paramètres
                </button>
            </div>
        </form>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-info-circle text-sc me-2"></i>À propos</div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Application</span><strong><?= e(APP_NAME) ?></strong></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Version</span><strong><?= e(APP_VERSION) ?></strong></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Environnement</span><strong><?= APP_DEBUG ? 'Développement' : 'Production' ?></strong></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Version PHP</span><strong><?= e(PHP_VERSION) ?></strong></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Base de données</span><strong>MySQL / MariaDB</strong></div>
            </div>
        </div>

        <div class="card border-info">
            <div class="card-header text-info"><i class="bi bi-shield-check me-2"></i>Sécurité déployée</div>
            <div class="card-body">
                <ul class="list-unstyled text-small mb-0">
                    <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i>Mots de passe hachés (bcrypt)</li>
                    <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i>Requêtes SQL préparées (PDO)</li>
                    <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i>Jetons CSRF sur tous les formulaires</li>
                    <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i>Échappement XSS systématique</li>
                    <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i>Contrôle d'accès par rôle</li>
                    <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i>Limitation des tentatives de connexion</li>
                    <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i>Expiration des sessions inactives</li>
                    <li class="mb-0"><i class="bi bi-check2 text-success me-1"></i>Journal des opérations horodaté</li>
                </ul>
            </div>
        </div>
    </div>
</div>
