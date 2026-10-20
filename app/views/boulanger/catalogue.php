<?php
/**
 * F04 : catalogue des produits.
 * @var array  $produits
 * @var string $recherche
 * @var array  $panier  produit_id => quantite
 */
$nbPanier = array_sum($panier);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-0">Catalogue farine</h1>
        <p class="text-muted small mb-0">Sélectionnez les produits puis validez votre commande.</p>
    </div>
    <?php if ($nbPanier > 0): ?>
        <a href="<?= e(url('boulanger/commande/nouvelle')) ?>" class="btn btn-nma">
            <i class="bi bi-basket2 me-2"></i>Voir ma commande (<?= (int) $nbPanier ?>)
        </a>
    <?php endif; ?>
</div>

<form method="get" action="<?= e(url('boulanger/catalogue')) ?>" class="mb-3">
    <input type="hidden" name="r" value="boulanger/catalogue">
    <div class="input-group">
        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
        <input type="search" class="form-control" name="q" value="<?= e($recherche) ?>"
               placeholder="Rechercher une farine (ex. T55, semoule...)">
        <button class="btn btn-nma" type="submit">Rechercher</button>
    </div>
</form>

<?php if ($produits === []): ?>
    <div class="card"><div class="nma-empty"><i class="bi bi-search"></i>Aucun produit ne correspond à votre recherche.</div></div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($produits as $p):
            $enStock   = (int) $p['stock_disponible'];
            $dispo     = $enStock > 0;
            $quantitePanier = (int) ($panier[(int) $p['id']] ?? 0);
            ?>
            <div class="col-12 col-md-6 col-xxl-4">
                <div class="card nma-produit-card h-100">
                    <div class="nma-produit-visuel">
                        <?php if (!empty($p['image'])): ?>
                            <img src="<?= e(asset($p['image'])) ?>" alt="<?= e($p['libelle']) ?>" loading="lazy">
                        <?php else: ?>
                            <i class="bi bi-bag"></i>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <h2 class="h6 fw-bold mb-0"><?= e($p['libelle']) ?></h2>
                            <?php if ($quantitePanier > 0): ?>
                                <span class="badge text-bg-success"><?= $quantitePanier ?> dans la commande</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-small text-muted mb-2"><?= e($p['description'] ?? '') ?></p>
                        <div class="nma-produit-prix mb-2">
                            <?= e(fcfa($p['prix_unitaire'] !== null ? (float) $p['prix_unitaire'] : null)) ?>
                            <small>/ <?= e($p['unite']) ?></small>
                        </div>
                        <div class="d-flex flex-wrap gap-2 text-small mb-2">
                            <span class="nma-pill"><i class="bi bi-tag"></i><?= e($p['reference']) ?></span>
                            <span class="nma-pill"><i class="bi bi-box-seam"></i><?= e($p['unite']) ?></span>
                        </div>
                        <?php if ($dispo): ?>
                            <div class="text-small text-success">
                                <i class="bi bi-check2-circle me-1"></i><?= $enStock ?> <?= e($p['unite']) ?> disponibles
                            </div>
                        <?php else: ?>
                            <div class="text-small text-danger">
                                <i class="bi bi-x-circle me-1"></i>Produit momentanément indisponible
                            </div>
                        <?php endif; ?>

                        <?php if ($dispo): ?>
                            <form method="post" action="<?= e(url('boulanger/panier/ajouter')) ?>" class="mt-3">
                                <?= csrf_field() ?>
                                <input type="hidden" name="produit_id" value="<?= (int) $p['id'] ?>">
                                <input type="hidden" name="retour" value="boulanger/catalogue">
                                <div class="d-flex gap-2">
                                    <div class="input-group" style="max-width:150px">
                                        <button class="btn btn-outline-secondary" type="button" data-quantite="moins" data-cible="qte-<?= (int) $p['id'] ?>" aria-label="Diminuer">
                                            <i class="bi bi-dash"></i>
                                        </button>
                                        <input type="number" class="form-control text-center" id="qte-<?= (int) $p['id'] ?>"
                                               name="quantite" value="1" min="1" max="<?= max(1, $enStock) ?>" inputmode="numeric">
                                        <button class="btn btn-outline-secondary" type="button" data-quantite="plus" data-cible="qte-<?= (int) $p['id'] ?>" aria-label="Augmenter">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                    <button type="submit" class="btn btn-nma flex-grow-1">
                                        <i class="bi bi-cart-plus me-2"></i>Ajouter
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <button class="btn btn-secondary w-100 mt-3" disabled>
                                <i class="bi bi-slash-circle me-2"></i>Indisponible
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
