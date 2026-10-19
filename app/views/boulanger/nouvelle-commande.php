<?php
/**
 * F05 : recapitulatif du panier et validation de la commande.
 * @var array  $lignes
 * @var array  $total           ['sacs','montant','montant_connu']
 * @var string $dateMinimum
 * @var int    $delaiHeures
 * @var string $adresse
 * @var string $zone
 * @var string $commentaire
 */
$aDesManques = false;
foreach ($lignes as $l) {
    if (!$l['suffisant']) {
        $aDesManques = true;
    }
}
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-0">Ma commande</h1>
        <p class="text-muted small mb-0">Vérifiez les quantités puis indiquez la livraison souhaitée.</p>
    </div>
    <a href="<?= e(url('boulanger/catalogue')) ?>" class="btn btn-outline-nma">
        <i class="bi bi-plus-lg me-1"></i>Ajouter des produits
    </a>
</div>

<?php if ($lignes === []): ?>
    <div class="card">
        <div class="nma-empty">
            <i class="bi bi-basket"></i>
            Votre commande est vide.
            <div class="mt-3">
                <a href="<?= e(url('boulanger/catalogue')) ?>" class="btn btn-nma">
                    <i class="bi bi-grid-3x3-gap me-2"></i>Choisir des produits
                </a>
            </div>
        </div>
    </div>
    <?php return; ?>
<?php endif; ?>

<?php if ($aDesManques): ?>
    <div class="alert alert-warning d-flex">
        <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>
        <div>
            Certaines quantités dépassent le stock disponible : la commande pourra être partiellement servie
            ou refusée par le gestionnaire. Merci d'ajuster si nécessaire.
        </div>
    </div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-basket2 text-nma me-2"></i>Produits sélectionnés</span>
                <form method="post" action="<?= e(url('boulanger/panier/vider')) ?>" class="d-inline"
                      data-confirmer="Vider toute la commande en cours ?">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-link text-danger p-0">Vider</button>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th class="text-end" style="width:170px">Quantité</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($lignes as $l): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= e($l['libelle']) ?></div>
                                    <div class="text-small text-muted">
                                        <?= e($l['reference']) ?> · <?= e(fcfa($l['prix_unitaire'] !== null ? (float) $l['prix_unitaire'] : null)) ?> / <?= e($l['unite']) ?>
                                    </div>
                                    <?php if (!$l['suffisant']): ?>
                                        <div class="text-small text-danger"><i class="bi bi-exclamation-circle me-1"></i>Stock disponible : <?= (int) $l['stock_disponible'] ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" action="<?= e(url('boulanger/panier/modifier')) ?>" class="d-flex gap-1 justify-content-end">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="produit_id" value="<?= (int) $l['produit_id'] ?>">
                                        <input type="number" class="form-control form-control-sm text-center" name="quantite"
                                               value="<?= (int) $l['quantite'] ?>" min="0" max="9999" inputmode="numeric"
                                               style="max-width:80px">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Mettre à jour">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                    </form>
                                    <div class="text-end text-small text-muted mt-1">0 = retirer</div>
                                </td>
                                <td class="text-end nma-num">
                                    <?= $l['prix_unitaire'] !== null ? e(fcfa((float) $l['prix_unitaire'] * (int) $l['quantite'])) : '—' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th>Total</th>
                                <th class="text-end nma-num"><?= (int) $total['sacs'] ?> sac(s)</th>
                                <th class="text-end nma-num"><?= $total['montant_connu'] ? e(fcfa((float) $total['montant'])) : 'Sur devis' ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <form method="post" action="<?= e(url('boulanger/commande/enregistrer')) ?>" class="card"
              data-confirmer="Confirmer l'envoi de cette commande à NMA Sanders ?">
            <?= csrf_field() ?>
            <div class="card-header"><i class="bi bi-truck text-nma me-2"></i>Livraison souhaitée</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="date_livraison_souhaitee">Date de livraison souhaitée</label>
                    <input type="date" class="form-control" id="date_livraison_souhaitee"
                           name="date_livraison_souhaitee" value="<?= e($dateMinimum) ?>"
                           min="<?= e($dateMinimum) ?>" required>
                    <div class="form-text">
                        <i class="bi bi-info-circle me-1"></i>Délai minimum de <?= (int) $delaiHeures ?> h après la commande
                        (date la plus proche : <?= e(date_fr($dateMinimum)) ?>).
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="adresse_livraison">Adresse de livraison</label>
                    <textarea class="form-control" id="adresse_livraison" name="adresse_livraison" rows="2"
                              maxlength="255" required><?= e($adresse) ?></textarea>
                    <?php if ($zone): ?>
                        <div class="form-text"><i class="bi bi-geo-alt me-1"></i>Zone habituelle : <?= e($zone) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="commentaire">Commentaire (facultatif)</label>
                    <textarea class="form-control" id="commentaire" name="commentaire" rows="2" maxlength="500"
                              placeholder="Ex. livrer avant 10 h, appeler à l'arrivée..."><?= e($commentaire) ?></textarea>
                </div>

                <div class="nma-timeline-note mb-3">
                    <i class="bi bi-clock-history me-1"></i>
                    Votre commande sera <strong>« Reçue »</strong> dès l'envoi. Vous pourrez la modifier ou l'annuler
                    tant qu'elle n'est pas validée par le gestionnaire.
                </div>
            </div>
            <div class="card-footer bg-white border-0 pt-0">
                <div class="nma-sticky-actions">
                    <button type="submit" class="btn btn-nma btn-lg w-100">
                        <i class="bi bi-send-check me-2"></i>Envoyer ma commande
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
