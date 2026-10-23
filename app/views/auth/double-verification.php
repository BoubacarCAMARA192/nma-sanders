<?php
/**
 * Double verification (2e facteur) pour les comptes gestionnaire et administrateur.
 * @var string|null $codeDemo
 */
?>
<h2 class="h4 fw-bold mb-1">Vérification en deux étapes</h2>
<p class="text-muted small mb-4">
    Pour renforcer la sécurité des comptes de gestion, un code à 6 chiffres est demandé à chaque connexion.
</p>

<?php if (!empty($codeDemo)): ?>
    <div class="alert alert-warning small">
        <i class="bi bi-tools me-1"></i>
        <strong>Mode développement :</strong> code de vérification = <span class="fw-bold"><?= e($codeDemo) ?></span>.
        En production, il est transmis par SMS ou e-mail (F19).
    </div>
<?php endif; ?>

<form method="post" action="<?= e(url('double-verification')) ?>" novalidate>
    <?= csrf_field() ?>

    <div class="mb-3">
        <label class="form-label" for="code">Code de vérification</label>
        <input type="text" class="form-control form-control-lg text-center sc-num" id="code" name="code"
               inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus
               placeholder="000000" style="letter-spacing:.5rem;font-size:1.5rem">
    </div>

    <button type="submit" class="btn btn-sc w-100 btn-lg mb-3">
        <i class="bi bi-shield-check me-2"></i>Valider le code
    </button>

    <a href="<?= e(logout_url()) ?>" class="btn btn-link w-100 text-secondary">
        <i class="bi bi-arrow-left me-1"></i>Revenir à la connexion
    </a>
</form>
