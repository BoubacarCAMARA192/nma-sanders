<?php
/**
 * Parametres applicatifs (regles de gestion ajustables).
 * @var array $parametres
 */
$descriptions = [
    'delai_minimum_heures'        => "DÃ©lai minimum imposÃ© au boulanger entre le dÃ©pÃ´t de la commande et la date de livraison souhaitÃ©e.",
    'max_tentatives_connexion'    => "Nombre d'Ã©checs de connexion avant blocage temporaire du compte.",
    'duree_blocage_minutes'       => "DurÃ©e du blocage du compte aprÃ¨s Ã©checs rÃ©pÃ©tÃ©s.",
    'session_duree_minutes'       => "DÃ©connexion automatique aprÃ¨s cette durÃ©e d'inactivitÃ©.",
    'jours_historique_par_defaut' => "Profondeur d'historique proposÃ©e par dÃ©faut dans les Ã©crans de suivi.",
];
?>
<h1 class="h4 fw-bold mb-1">ParamÃ¨tres de la plateforme</h1>
<p class="text-muted small mb-3">
    Ces valeurs pilotent les rÃ¨gles de gestion et la sÃ©curitÃ© de la plateforme. Elles s'appliquent immÃ©diatement.
</p>

<div class="row g-3">
    <div class="col-12 col-lg-8">
        <form method="post" action="<?= e(url('administrateur/parametres')) ?>" class="card">
            <?= csrf_field() ?>
            <div class="card-header"><i class="bi bi-sliders text-sc me-2"></i>RÃ¨gles de gestion et sÃ©curitÃ©</div>
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
                            <div class="form-text text-muted">ParamÃ¨tre systÃ¨me non modifiable depuis l'interface.</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="card-footer bg-white">
                <button type="submit" class="btn btn-sc w-100">
                    <i class="bi bi-save me-2"></i>Enregistrer les paramÃ¨tres
                </button>
            </div>
        </form>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-info-circle text-sc me-2"></i>Ã€ propos</div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Application</span><strong><?= e(APP_NAME) ?></strong></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Version</span><strong><?= e(APP_VERSION) ?></strong></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Environnement</span><strong><?= APP_DEBUG ? 'DÃ©veloppement' : 'Production' ?></strong></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Version PHP</span><strong><?= e(PHP_VERSION) ?></strong></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Base de donnÃ©es</span><strong>MySQL / MariaDB</strong></div>
            </div>
        </div>

        <div class="card border-info">
            <div class="card-header text-info"><i class="bi bi-shield-check me-2"></i>SÃ©curitÃ© dÃ©ployÃ©e</div>
            <div class="card-body">
                <ul class="list-unstyled text-small mb-0">
                    <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i>Mots de passe hachÃ©s (bcrypt)</li>
                    <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i>RequÃªtes SQL prÃ©parÃ©es (PDO)</li>
                    <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i>Jetons CSRF sur tous les formulaires</li>
                    <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i>Ã‰chappement XSS systÃ©matique</li>
                    <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i>ContrÃ´le d'accÃ¨s par rÃ´le</li>
                    <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i>Limitation des tentatives de connexion</li>
                    <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i>Expiration des sessions inactives</li>
                    <li class="mb-0"><i class="bi bi-check2 text-success me-1"></i>Journal des opÃ©rations horodatÃ©</li>
                </ul>
            </div>
        </div>
    </div>
</div>

