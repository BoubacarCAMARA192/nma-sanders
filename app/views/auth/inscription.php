<?php
/**
 * Demande d'inscription (F02) : le compte est cree inactif puis active
 * par l'administrateur apres contact commercial.
 * @var array<string,string> $donnees
 */
$d = static fn (string $cle): string => (string) ($donnees[$cle] ?? '');
?>
<div class="text-center mb-4">
    <span class="sc-brand-badge mb-2" style="width:46px;height:46px;font-size:1.45rem;">
        <i class="bi bi-shop"></i>
    </span>
    <h2 class="h4 fw-bold mb-1">Créer un compte</h2>
    <p class="text-muted small mb-0">Votre demande est transmise à notre service commercial, puis activée après contact.</p>
</div>

<form method="post" action="<?= e(url('inscription')) ?>" novalidate>
    <?= csrf_field() ?>

    <div class="mb-3">
        <label class="form-label" for="nom_boulangerie">Nom de l'établissement</label>
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-shop"></i></span>
            <input type="text" class="form-control" id="nom_boulangerie" name="nom_boulangerie"
                   required placeholder="Ex. Pains du Plateau" value="<?= e($d('nom_boulangerie')) ?>">
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="nom">Nom du responsable</label>
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-person"></i></span>
            <input type="text" class="form-control" id="nom" name="nom"
                   required placeholder="Prénom et nom" value="<?= e($d('nom')) ?>">
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6">
            <label class="form-label" for="telephone">Téléphone / WhatsApp</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-phone"></i></span>
                <input type="tel" class="form-control" id="telephone" name="telephone"
                       required placeholder="+221 77 000 00 00" value="<?= e($d('telephone')) ?>">
            </div>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label" for="ville">Ville</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-geo"></i></span>
                <input type="text" class="form-control" id="ville" name="ville" list="villes"
                       placeholder="Dakar" value="<?= e($d('ville')) ?>">
                <datalist id="villes">
                    <option value="Dakar"></option><option value="Rufisque"></option>
                    <option value="Thiès"></option><option value="Mbour"></option>
                    <option value="Saint-Louis"></option><option value="Touba"></option>
                </datalist>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="email">Adresse e-mail</label>
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-envelope"></i></span>
            <input type="email" class="form-control" id="email" name="email" inputmode="email"
                   required placeholder="vous@exemple.sn" value="<?= e($d('email')) ?>">
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="zone_livraison">Zone de livraison <span class="text-muted">(optionnel)</span></label>
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="bi bi-geo-alt"></i></span>
            <input type="text" class="form-control" id="zone_livraison" name="zone_livraison"
                   placeholder="Ex. Plateau, Médina, Grand-Yoff…" value="<?= e($d('zone_livraison')) ?>">
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6">
            <label class="form-label" for="mot_de_passe">Mot de passe</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe"
                       required autocomplete="new-password" placeholder="8 caractères min.">
            </div>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label" for="mot_de_passe_confirmation">Confirmation</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-lock-fill"></i></span>
                <input type="password" class="form-control" id="mot_de_passe_confirmation"
                       name="mot_de_passe_confirmation" required autocomplete="new-password" placeholder="••••••••">
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-sc w-100 btn-lg mb-3">
        <i class="bi bi-send me-2"></i>Créer mon compte
    </button>

    <div class="alert alert-light border small mb-0" role="note">
        <i class="bi bi-info-circle me-1 text-sc"></i>
        Le compte reste <strong>inactif</strong> jusqu'à l'appel de notre service commercial
        (<a href="tel:+221771249186" class="fw-semibold">+221 77 124 91 86</a>).
    </div>
</form>

<hr class="my-4">
<p class="small text-muted mb-0 text-center">
    Déjà client ? <a href="<?= e(url('connexion')) ?>" class="fw-semibold">Se connecter</a>
</p>
