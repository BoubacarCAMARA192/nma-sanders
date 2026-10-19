<?php
/**
 * Definition du nouveau mot de passe via jeton.
 * @var string $token
 * @var string $email
 */
?>
<h2 class="h4 fw-bold mb-1">Nouveau mot de passe</h2>
<p class="text-muted small mb-4">Compte : <strong><?= e($email) ?></strong></p>

<form method="post" action="<?= e(url('reinitialiser')) ?>" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">

    <div class="mb-3">
        <label class="form-label" for="mot_de_passe">Nouveau mot de passe</label>
        <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required autofocus
               autocomplete="new-password" placeholder="Au moins 8 caractères">
        <div class="form-text">8 caractères minimum, avec majuscules, minuscules et chiffres.</div>
    </div>

    <div class="mb-4">
        <label class="form-label" for="mot_de_passe_confirmation">Confirmer le mot de passe</label>
        <input type="password" class="form-control" id="mot_de_passe_confirmation"
               name="mot_de_passe_confirmation" required autocomplete="new-password">
    </div>

    <button type="submit" class="btn btn-nma w-100 btn-lg">
        <i class="bi bi-check2-circle me-2"></i>Enregistrer le mot de passe
    </button>
</form>
