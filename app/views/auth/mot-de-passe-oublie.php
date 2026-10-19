<?php
/**
 * Demande de reinitialisation du mot de passe.
 */
$lienDemo = APP_DEBUG ? ($_SESSION['_reset_demo_link'] ?? null) : null;
?>
<h2 class="h4 fw-bold mb-1">Mot de passe oublié</h2>
<p class="text-muted small mb-4">
    Indiquez l'adresse e-mail de votre compte : votre demande est transmise à NMA Sanders qui vous
    communiquera un lien de réinitialisation valable une heure.
</p>

<?php if (!empty($lienDemo)): ?>
    <div class="alert alert-warning small">
        <i class="bi bi-tools me-1"></i><strong>Mode développement :</strong>
        <a href="<?= e($lienDemo) ?>" class="fw-semibold">ouvrir le lien de réinitialisation généré</a>.
        En production, ce lien est envoyé par e-mail ou SMS.
    </div>
<?php endif; ?>

<form method="post" action="<?= e(url('mot-de-passe-oublie')) ?>" novalidate>
    <?= csrf_field() ?>

    <div class="mb-3">
        <label class="form-label" for="email">Adresse e-mail du compte</label>
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-envelope"></i></span>
            <input type="email" class="form-control" id="email" name="email" required autofocus
                   inputmode="email" placeholder="boulanger@exemple.sn">
        </div>
    </div>

    <button type="submit" class="btn btn-nma w-100 btn-lg mb-3">
        <i class="bi bi-send me-2"></i>Envoyer ma demande
    </button>
</form>

<a href="<?= e(url('connexion')) ?>" class="btn btn-link w-100 text-secondary">
    <i class="bi bi-arrow-left me-1"></i>Revenir à la connexion
</a>
