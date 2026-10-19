<?php
/**
 * F01 : formulaire de connexion.
 * @var string|null $email
 */
?>
<div class="text-center mb-4">
    <span class="nma-brand-badge mb-2" style="width:46px;height:46px;font-size:1.45rem;">
        <i class="bi bi-box-arrow-in-right"></i>
    </span>
    <h2 class="h4 fw-bold mb-1">Connexion</h2>
    <p class="text-muted small mb-0">Saisissez vos identifiants pour accéder à votre espace.</p>
</div>

<form method="post" action="<?= e(url('connexion')) ?>" novalidate>
    <?= csrf_field() ?>

    <div class="mb-3">
        <label class="form-label" for="email">Adresse e-mail</label>
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-envelope"></i></span>
            <input type="email" class="form-control" id="email" name="email" autocomplete="username"
                   inputmode="email" required autofocus
                   placeholder="boulanger@exemple.sn"
                   value="<?= e($email ?? '') ?>">
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="mot_de_passe">Mot de passe</label>
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
            <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe"
                   autocomplete="current-password" required placeholder="••••••••">
            <button class="btn btn-outline-secondary" type="button" id="voirMdp" aria-label="Afficher le mot de passe">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>

    <button type="submit" class="btn btn-nma w-100 btn-lg mb-3">
        <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
    </button>

    <div class="d-flex justify-content-between align-items-center small">
        <a href="<?= e(url('mot-de-passe-oublie')) ?>" class="link-secondary">Mot de passe oublié ?</a>
        <span class="text-muted"><i class="bi bi-shield-lock me-1"></i>Connexion sécurisée</span>
    </div>
</form>

<?php if (APP_DEMO): ?>
    <div class="nma-demo-box mt-4">
        <div class="small fw-semibold mb-2">
            <i class="bi bi-stars me-1 text-nma"></i>Comptes de démonstration — cliquez pour remplir :
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-outline-nma nma-demo-chip nma-demo-chip-admin"
                    data-email="admin@nmasanders.sn" data-mdp="Admin@2026">
                <i class="bi bi-shield-lock me-1"></i>Administrateur
            </button>
            <button type="button" class="btn btn-sm btn-outline-nma nma-demo-chip"
                    data-email="gestionnaire@nmasanders.sn" data-mdp="Gestion@2026">
                <i class="bi bi-clipboard-check me-1"></i>Gestionnaire
            </button>
            <button type="button" class="btn btn-sm btn-outline-nma nma-demo-chip"
                    data-email="boulanger@demo.sn" data-mdp="Boulanger@2026">
                <i class="bi bi-shop me-1"></i>Boulanger
            </button>
        </div>
    </div>
<?php endif; ?>

<hr class="my-4">
<p class="small text-muted mb-0 text-center">
    <i class="bi bi-shop me-1"></i>Pas encore de compte ?
    <a href="<?= e(url('inscription')) ?>" class="fw-semibold">Créer un compte</a>
    ou appelez le
    <a href="tel:+221338390000" class="fw-semibold">+221 33 839 00 00</a>.
</p>

<script>
document.getElementById('voirMdp').addEventListener('click', function () {
    var champ = document.getElementById('mot_de_passe');
    var icone = this.querySelector('i');
    var visible = champ.type === 'text';
    champ.type = visible ? 'password' : 'text';
    icone.className = visible ? 'bi bi-eye' : 'bi bi-eye-slash';
    champ.focus();
});
</script>
