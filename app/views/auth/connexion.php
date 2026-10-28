<?php
/**
 * F01 : formulaire de connexion.
 * @var string|null $email
 */
?>
<div class="sc-connexion-entete text-center mb-4 sc-reveal" style="--retard:.05s">
    <img src="<?= e(asset('assets/img/logo-sc.png')) ?>" alt="<?= e(APP_NAME) ?>" class="sc-connexion-logo">
    <span class="sc-pill mb-2"><?= e(APP_NAME) ?></span>
    <h2 class="h4 fw-bold mb-1">Connexion à votre espace</h2>
    <p class="text-muted small mb-0">Commandes, livraisons et suivi réunis au même endroit.</p>
</div>

<form method="post" action="<?= e(url('connexion')) ?>" novalidate id="scFormulaireConnexion">
    <?= csrf_field() ?>

    <div class="mb-3 sc-connexion-champ sc-reveal" style="--retard:.14s">
        <label class="form-label" for="email">Adresse e-mail</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" class="form-control" id="email" name="email" autocomplete="username"
                   inputmode="email" autocapitalize="none" spellcheck="false" required autofocus
                   placeholder="boulanger@exemple.sn"
                   value="<?= e($email ?? '') ?>">
        </div>
    </div>

    <div class="mb-3 sc-connexion-champ sc-reveal" style="--retard:.2s">
        <div class="d-flex justify-content-between align-items-center">
            <label class="form-label" for="mot_de_passe">Mot de passe</label>
            <a href="<?= e(url('mot-de-passe-oublie')) ?>" class="small fw-semibold">Mot de passe oublié ?</a>
        </div>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe"
                   autocomplete="current-password" required placeholder="••••••••">
            <button class="btn btn-outline-secondary" type="button" id="voirMdp" aria-label="Afficher le mot de passe">
                <i class="bi bi-eye"></i>
            </button>
        </div>
        <div class="sc-capslock small mt-1" id="scCapsLock" hidden>
            <i class="bi bi-exclamation-circle me-1"></i>Verr. Maj activée
        </div>
    </div>

    <button type="submit" class="btn btn-sc sc-btn-shine sc-reveal w-100 btn-lg mb-2" style="--retard:.26s">
        <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
    </button>

    <div class="sc-connexion-securite sc-reveal" style="--retard:.32s">
        <i class="bi bi-shield-check"></i>Connexion sécurisée · session limitée en cas d'inactivité
    </div>
</form>

<div class="sc-connexion-actions">
    <a href="<?= e(url('inscription')) ?>" class="sc-connexion-action sc-reveal" style="--retard:.38s">
        <span class="ico"><i class="bi bi-shop"></i></span>
        <span class="txt">
            <strong>Pas encore de compte ?</strong>
            <small>Créer un compte boulanger</small>
        </span>
        <i class="bi bi-arrow-right fleche"></i>
    </a>
    <a href="tel:+221771249186" class="sc-connexion-action sc-reveal" style="--retard:.44s">
        <span class="ico"><i class="bi bi-telephone"></i></span>
        <span class="txt">
            <strong>Service commercial</strong>
            <small>+221 77 124 91 86</small>
        </span>
        <i class="bi bi-telephone-outbound fleche"></i>
    </a>
</div>

<div class="sc-connexion-garanties sc-reveal" style="--retard:.5s">
    <span><i class="bi bi-lock-fill me-1"></i>Paiement sécurisé</span>
    <span><i class="bi bi-phone me-1"></i>Wave &amp; Orange Money</span>
    <span><i class="bi bi-truck me-1"></i>Suivi de livraison</span>
</div>

<div class="sc-toast" id="scToast" role="status" aria-live="polite"></div>

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
    var alerte = document.getElementById('scCapsLock');
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
