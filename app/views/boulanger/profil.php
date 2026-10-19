<?php
/**
 * F03 : profil du boulanger et changement de mot de passe.
 * @var array $fiche
 * @var array $commandes
 */
?>
<h1 class="h4 fw-bold mb-1">Mon profil</h1>
<p class="text-muted small mb-3">Ces informations servent à la préparation et à la livraison de vos commandes.</p>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <form method="post" action="<?= e(url('boulanger/profil')) ?>" class="card">
            <?= csrf_field() ?>
            <div class="card-header"><i class="bi bi-shop text-nma me-2"></i>Identité et boulangerie</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="nom">Nom du responsable</label>
                        <input type="text" class="form-control" id="nom" name="nom" required maxlength="150"
                               value="<?= e($utilisateur['nom']) ?>">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="telephone">Téléphone</label>
                        <input type="tel" class="form-control" id="telephone" name="telephone" maxlength="30"
                               inputmode="tel" value="<?= e($utilisateur['telephone'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="email">Adresse e-mail (identifiant de connexion)</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?= e($utilisateur['email']) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="nom_boulangerie">Nom de la boulangerie</label>
                        <input type="text" class="form-control" id="nom_boulangerie" name="nom_boulangerie" required
                               maxlength="190" value="<?= e($fiche['nom_boulangerie'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="adresse">Adresse de livraison</label>
                        <textarea class="form-control" id="adresse" name="adresse" rows="2" required
                                  maxlength="255"><?= e($fiche['adresse'] ?? '') ?></textarea>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="ville">Ville / commune</label>
                        <input type="text" class="form-control" id="ville" name="ville" maxlength="100"
                               value="<?= e($fiche['ville'] ?? 'Dakar') ?>">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="zone_livraison">Zone de livraison</label>
                        <input type="text" class="form-control" id="zone_livraison" name="zone_livraison" maxlength="100"
                               value="<?= e($fiche['zone_livraison'] ?? '') ?>" placeholder="Ex. Dakar Plateau">
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white border-0">
                <button type="submit" class="btn btn-nma w-100">
                    <i class="bi bi-save me-2"></i>Enregistrer mon profil
                </button>
            </div>
        </form>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-shield-lock text-nma me-2"></i>Sécurité du compte</div>
            <div class="card-body">
                <form method="post" action="<?= e(url('boulanger/profil/mot-de-passe')) ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="mot_de_passe_actuel">Mot de passe actuel</label>
                        <input type="password" class="form-control" id="mot_de_passe_actuel" name="mot_de_passe_actuel"
                               required autocomplete="current-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="nouveau_mot_de_passe">Nouveau mot de passe</label>
                        <input type="password" class="form-control" id="nouveau_mot_de_passe"
                               name="nouveau_mot_de_passe" required autocomplete="new-password">
                        <div class="form-text">8 caractères minimum, avec majuscules, minuscules et chiffres.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="nouveau_mot_de_passe_confirmation">Confirmation</label>
                        <input type="password" class="form-control" id="nouveau_mot_de_passe_confirmation"
                               name="nouveau_mot_de_passe_confirmation" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-outline-nma w-100">
                        <i class="bi bi-key me-2"></i>Changer le mot de passe
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-bar-chart text-nma me-2"></i>Mon activité</div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Commandes enregistrées</span><strong><?= array_sum($commandes) ?></strong></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Livrées</span><strong><?= (int) $commandes['livree'] ?></strong></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">En cours</span><strong><?= (int) ($commandes['recue'] + $commandes['validee'] + $commandes['en_preparation'] + $commandes['en_livraison']) ?></strong></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Dernière connexion</span><strong class="text-small"><?= e(datetime_fr($utilisateur['derniere_connexion'])) ?></strong></div>
            </div>
        </div>
    </div>
</div>
