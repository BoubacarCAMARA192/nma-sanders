<?php
/**
 * Parametres applicatifs (regles de gestion ajustables).
 * @var array $parametres
 */
$descriptions = [
    'delai_minimum_heures'        => "Délai minimum, exprimé en heures, imposé au boulanger entre le dépôt de la commande et la date de livraison souhaitée.",
    'capacite_sacs_par_jour'      => "Charge maximale de livraison par jour, en sacs. Au-delà, la journée est signalée comme surchargée. Mettre 0 pour désactiver le contrôle.",
    'max_tentatives_connexion'    => "Nombre d'échecs de connexion avant blocage temporaire du compte.",
    'duree_blocage_minutes'       => "Durée du blocage du compte après échecs répétés, exprimée en minutes.",
    'session_duree_minutes'       => "Déconnexion automatique après cette durée d'inactivité, exprimée en minutes.",
    'jours_historique_par_defaut' => "Profondeur d'historique proposée par défaut dans les écrans de suivi, exprimée en jours.",
];

$unites = [
    'delai_minimum_heures'        => 'heures',
    'capacite_sacs_par_jour'      => 'sacs / jour',
    'max_tentatives_connexion'    => 'tentatives',
    'duree_blocage_minutes'       => 'minutes',
    'session_duree_minutes'       => 'minutes',
    'jours_historique_par_defaut' => 'jours',
];
?>
<h1 class="h4 fw-bold mb-1">Paramètres de la plateforme</h1>
<p class="text-muted small mb-3">
    Ces valeurs pilotent les règles de gestion et la sécurité de la plateforme. Elles s'appliquent immédiatement.
</p>

<div class="row g-3">
    <div class="col-12 col-lg-9 mx-auto">
        <form method="post" action="<?= e(url('administrateur/parametres')) ?>" class="card">
            <?= csrf_field() ?>
            <div class="card-header"><i class="bi bi-sliders text-sc me-2"></i>Règles de gestion et sécurité</div>
            <div class="card-body">
                <?php foreach ($parametres as $cle => $valeur): ?>
                    <div class="mb-4">
                        <label class="form-label" for="<?= e($cle) ?>">
                            <?= e(parametre_fr($cle)) ?>
                        </label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="<?= e($cle) ?>" name="<?= e($cle) ?>"
                                   value="<?= e($valeur) ?>" min="0" required inputmode="numeric"
                                   <?= isset($descriptions[$cle]) ? '' : 'readonly' ?>>
                            <?php if (isset($unites[$cle])): ?>
                                <span class="input-group-text bg-white"><?= e($unites[$cle]) ?></span>
                            <?php endif; ?>
                        </div>
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
</div>
