<?php
/**
 * F01 : formulaire de connexion.
 * @var string|null $email
 */
?>
<div class="text-center mb-4">
    <span class="nma-brand-badge mb-2 nma-badge-pulse" style="width:48px;height:48px;font-size:1.5rem;">
        <i class="bi bi-box-arrow-in-right"></i>
    </span>
    <h2 class="h4 fw-bold mb-1">Bon retour !</h2>
    <p class="text-muted small mb-0">Connectez-vous pour retrouver vos commandes et votre suivi.</p>
</div>

<form method="post" action="<?= e(url('connexion')) ?>" novalidate id="nmaFormulaireConnexion">
    <?= csrf_field() ?>

    <div class="mb-3 nma-champ">
        <label class="form-label" for="email">Adresse e-mail</label>
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-envelope"></i></span>
            <input type="email" class="form-control" id="email" name="email" autocomplete="username"
                   inputmode="email" required autofocus
                   placeholder="boulanger@exemple.sn"
                   value="<?= e($email ?? '') ?>">
        </div>
    </div>

    <div class="mb-2 nma-champ">
        <label class="form-label" for="mot_de_passe">Mot de passe</label>
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
            <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe"
                   autocomplete="current-password" required placeholder="••••••••">
            <button class="btn btn-outline-secondary" type="button" id="voirMdp" aria-label="Afficher le mot de passe">
                <i class="bi bi-eye"></i>
            </button>
        </div>
        <div class="nma-capslock small mt-1" id="nmaCapsLock" hidden>
            <i class="bi bi-exclamation-circle me-1"></i>Verr. Maj activée
        </div>
    </div>

    <button type="submit" class="btn btn-nma nma-btn-shine w-100 btn-lg mb-3">
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
        <div class="nma-demo-comptes">
            <button type="button" class="nma-demo-compte nma-demo-chip nma-demo-compte-admin"
                    data-email="admin@nmasanders.sn" data-mdp="Admin@2026">
                <span class="ico"><i class="bi bi-shield-lock"></i></span>
                <span class="txt">
                    <strong>Administrateur</strong>
                    <small>admin@nmasanders.sn</small>
                </span>
                <i class="bi bi-arrow-repeat fleche"></i>
            </button>
            <button type="button" class="nma-demo-compte nma-demo-chip nma-demo-compte-gestion"
                    data-email="gestionnaire@nmasanders.sn" data-mdp="Gestion@2026">
                <span class="ico"><i class="bi bi-clipboard-check"></i></span>
                <span class="txt">
                    <strong>Gestionnaire</strong>
                    <small>gestionnaire@nmasanders.sn</small>
                </span>
                <i class="bi bi-arrow-repeat fleche"></i>
            </button>
            <button type="button" class="nma-demo-compte nma-demo-chip nma-demo-compte-shop"
                    data-email="boulanger@demo.sn" data-mdp="Boulanger@2026">
                <span class="ico"><i class="bi bi-shop"></i></span>
                <span class="txt">
                    <strong>Boulanger</strong>
                    <small>boulanger@demo.sn</small>
                </span>
                <i class="bi bi-arrow-repeat fleche"></i>
            </button>
            <button type="button" class="nma-demo-compte nma-demo-chip nma-demo-compte-client"
                    data-email="client1@demo.sn" data-mdp="Client1@2026">
                <span class="ico"><i class="bi bi-basket"></i></span>
                <span class="txt">
                    <strong>Client simple 1</strong>
                    <small>client1@demo.sn</small>
                </span>
                <i class="bi bi-arrow-repeat fleche"></i>
            </button>
            <button type="button" class="nma-demo-compte nma-demo-chip nma-demo-compte-client"
                    data-email="client2@demo.sn" data-mdp="Client2@2026">
                <span class="ico"><i class="bi bi-basket3"></i></span>
                <span class="txt">
                    <strong>Client simple 2</strong>
                    <small>client2@demo.sn</small>
                </span>
                <i class="bi bi-arrow-repeat fleche"></i>
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

<div class="nma-toast" id="nmaToast" role="status" aria-live="polite"></div>

<script>
document.getElementById('voirMdp').addEventListener('click', function () {
    var champ = document.getElementById('mot_de_passe');
    var icone = this.querySelector('i');
    var visible = champ.type === 'text';
    champ.type = visible ? 'password' : 'text';
    icone.className = visible ? 'bi bi-eye' : 'bi bi-eye-slash';
    champ.focus();
});

(function () {
    var champ = document.getElementById('mot_de_passe');
    var alerte = document.getElementById('nmaCapsLock');
    if (!champ || !alerte) {
        return;
    }
    function verifier(evenement) {
        var actif = false;
        if (evenement.getModifierState) {
            actif = evenement.getModifierState('CapsLock');
        }
        alerte.hidden = !actif;
    }
    champ.addEventListener('keydown', verifier);
    champ.addEventListener('keyup', verifier);
    champ.addEventListener('blur', function () { alerte.hidden = true; });
}());
</script>
