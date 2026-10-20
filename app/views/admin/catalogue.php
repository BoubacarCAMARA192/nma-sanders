<?php
/**
 * F18 : gestion du catalogue et de la disponibilite des produits.
 * @var array $produits
 * @var array $stats
 * @var array $alerte
 */
$enAlerte = [];
foreach ($alerte as $p) {
    $enAlerte[(int) $p['id']] = true;
}
?>
<div class="nma-catalogue-hero d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h1 class="h4">Catalogue des produits</h1>
        <p>
            <?= (int) $stats['actifs'] ?> produit(s) actif(s) sur <?= (int) $stats['total'] ?> ·
            <?= (int) $stats['stock'] ?> sac(s) en stock ·
            <?= (int) $stats['alerte'] ?> sous le seuil d'alerte
        </p>
    </div>
    <div class="nma-hero-actions">
        <button class="btn btn-light fw-semibold" data-bs-toggle="collapse" data-bs-target="#formProduit">
            <i class="bi bi-plus-lg me-2"></i>Ajouter un produit
        </button>
    </div>
</div>

<div class="collapse mb-3" id="formProduit">
    <div class="card border-nma">
        <div class="card-header bg-nma-soft"><i class="bi bi-box-seam text-nma me-2"></i>Nouveau produit</div>
        <form method="post" action="<?= e(url('administrateur/catalogue/enregistrer')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="0">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="reference">Référence</label>
                        <input type="text" class="form-control" id="reference" name="reference" required maxlength="50" placeholder="FAR-T55">
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label" for="libelle">Libellé</label>
                        <input type="text" class="form-control" id="libelle" name="libelle" required maxlength="150"
                               placeholder="Farine de blé tendre T55">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="description">Description</label>
                        <input type="text" class="form-control" id="description" name="description" maxlength="255">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="image">Visuel du produit</label>
                        <input type="text" class="form-control" id="image" name="image" list="listeVisuels"
                               placeholder="assets/img/produits/farine-boulangere.png" maxlength="255">
                        <datalist id="listeVisuels">
                            <option value="assets/img/produits/farine-boulangere.png"></option>
                            <option value="assets/img/produits/farine-boulangere-amelioree.png"></option>
                            <option value="assets/img/produits/farine-tapalapa.png"></option>
                            <option value="assets/img/produits/farine-patissiere.png"></option>
                        </datalist>
                        <div class="form-text">Chemin d'une image déjà présente dans <code>assets/img/produits/</code>.</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label" for="unite">Unité de vente</label>
                        <input type="text" class="form-control" id="unite" name="unite" required value="sac de 50 kg" maxlength="30">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label" for="prix_unitaire">Prix unitaire (FCFA)</label>
                        <input type="number" class="form-control" id="prix_unitaire" name="prix_unitaire" min="0" step="1"
                               placeholder="Optionnel" inputmode="numeric">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label" for="stock_disponible">Stock disponible</label>
                        <input type="number" class="form-control" id="stock_disponible" name="stock_disponible" min="0" value="0" required inputmode="numeric">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label" for="seuil_alerte">Seuil d'alerte</label>
                        <input type="number" class="form-control" id="seuil_alerte" name="seuil_alerte" min="0" value="20" required inputmode="numeric">
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="actif_new" name="actif" checked>
                            <label class="form-check-label" for="actif_new">Proposer immédiatement aux boulangers</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white">
                <button type="submit" class="btn btn-nma w-100"><i class="bi bi-check2 me-2"></i>Ajouter au catalogue</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if ($produits === []): ?>
            <div class="nma-empty"><i class="bi bi-box"></i>Aucun produit au catalogue.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th class="d-none d-md-table-cell">Unité</th>
                            <th class="text-end">Prix</th>
                            <th class="text-end">Stock</th>
                            <th>État</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($produits as $p): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if (!empty($p['image'])): ?>
                                        <img src="<?= e(asset($p['image'])) ?>" alt="" class="nma-table-visuel" loading="lazy">
                                    <?php else: ?>
                                        <span class="nma-table-visuel d-inline-flex align-items-center justify-content-center text-muted">
                                            <i class="bi bi-image"></i>
                                        </span>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-semibold"><?= e($p['libelle']) ?></div>
                                        <div class="text-small text-muted"><?= e($p['reference']) ?><?= !empty($p['description']) ? ' · ' . e($p['description']) : '' ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="d-none d-md-table-cell text-small"><?= e($p['unite']) ?></td>
                            <td class="text-end text-small nma-num"><?= e(fcfa($p['prix_unitaire'] !== null ? (float) $p['prix_unitaire'] : null)) ?></td>
                            <td class="text-end">
                                <span class="nma-num fw-semibold"><?= (int) $p['stock_disponible'] ?></span>
                                <?php if (isset($enAlerte[(int) $p['id']])): ?>
                                    <i class="bi bi-exclamation-triangle-fill text-warning ms-1" title="Stock sous le seuil d'alerte"></i>
                                <?php endif; ?>
                                <div class="text-small text-muted">seuil <?= (int) $p['seuil_alerte'] ?></div>
                            </td>
                            <td>
                                <span class="badge text-bg-<?= (int) $p['actif'] === 1 ? 'success' : 'secondary' ?>">
                                    <?= (int) $p['actif'] === 1 ? 'Actif' : 'Retiré' ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <button class="btn btn-sm btn-outline-nma" data-bs-toggle="modal"
                                            data-bs-target="#modalProduit<?= (int) $p['id'] ?>" title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <form method="post" action="<?= e(url('administrateur/catalogue/stock')) ?>" class="d-flex gap-1">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                        <input type="number" class="form-control form-control-sm text-center" name="delta"
                                               placeholder="±" style="max-width:70px" inputmode="numeric" title="Ajuster le stock (+/-)">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Appliquer l'ajustement">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                    </form>

                                    <form method="post" action="<?= e(url('administrateur/catalogue/activer')) ?>"
                                          data-confirmer="<?= (int) $p['actif'] === 1 ? 'Retirer ce produit du catalogue boulanger ?' : 'Remettre ce produit au catalogue ?' ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-<?= (int) $p['actif'] === 1 ? 'danger' : 'success' ?>"
                                                title="<?= (int) $p['actif'] === 1 ? 'Retirer' : 'Activer' ?>">
                                            <i class="bi bi-<?= (int) $p['actif'] === 1 ? 'eye-slash' : 'eye' ?>"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php foreach ($produits as $p): ?>
    <div class="modal fade" id="modalProduit<?= (int) $p['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form method="post" action="<?= e(url('administrateur/catalogue/enregistrer')) ?>" class="modal-content">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier « <?= e($p['libelle']) ?> »</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Référence</label>
                            <input type="text" class="form-control" name="reference" required maxlength="50" value="<?= e($p['reference']) ?>">
                        </div>
                        <div class="col-12 col-md-8">
                            <label class="form-label">Libellé</label>
                            <input type="text" class="form-control" name="libelle" required maxlength="150" value="<?= e($p['libelle']) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <input type="text" class="form-control" name="description" maxlength="255" value="<?= e($p['description'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Visuel du produit</label>
                            <div class="nma-form-visuel">
                                <?php if (!empty($p['image'])): ?>
                                    <img src="<?= e(asset($p['image'])) ?>" alt="">
                                <?php else: ?>
                                    <span class="nma-table-visuel d-inline-flex align-items-center justify-content-center text-muted"><i class="bi bi-image"></i></span>
                                <?php endif; ?>
                                <div class="flex-grow-1">
                                    <input type="text" class="form-control" name="image" list="listeVisuels"
                                           value="<?= e($p['image'] ?? '') ?>" maxlength="255"
                                           placeholder="assets/img/produits/farine-boulangere.png">
                                    <small>Laissez vide pour afficher l'icône générique.</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Unité de vente</label>
                            <input type="text" class="form-control" name="unite" required maxlength="30" value="<?= e($p['unite']) ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Prix unitaire (FCFA)</label>
                            <input type="number" class="form-control" name="prix_unitaire" min="0" step="1"
                                   value="<?= $p['prix_unitaire'] !== null ? (int) $p['prix_unitaire'] : '' ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Stock disponible</label>
                            <input type="number" class="form-control" name="stock_disponible" min="0" required
                                   value="<?= (int) $p['stock_disponible'] ?>" readonly>
                            <div class="form-text">Utilisez l'ajustement de stock (+/-) pour éviter les écarts d'inventaire.</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Seuil d'alerte</label>
                            <input type="number" class="form-control" name="seuil_alerte" min="0" required value="<?= (int) $p['seuil_alerte'] ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-nma"><i class="bi bi-save me-1"></i>Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
<?php endforeach; ?>
