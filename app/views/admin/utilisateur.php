<?php
/**
 * Fiche detaillee d'un compte : modification, activation, tracabilite.
 * @var array $compte
 * @var array $operations
 * @var array $commandes
 */
$estBoulanger = $compte['role'] === 'boulanger';
?>
<nav aria-label="fil d'ariane">
    <ol class="breadcrumb small mb-1">
        <li class="breadcrumb-item"><a href="<?= e(url('administrateur/utilisateurs')) ?>">Utilisateurs</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= e($compte['nom']) ?></li>
    </ol>
</nav>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-0">
            <?= e($compte['nom']) ?>
            <span class="badge text-bg-<?= (int) $compte['actif'] === 1 ? 'success' : 'secondary' ?>">
                <?= (int) $compte['actif'] === 1 ? 'Actif' : 'Inactif' ?>
            </span>
        </h1>
        <p class="text-muted small mb-0">
            <?= e(ucfirst($compte['role'])) ?> Â· <?= e($compte['email']) ?> Â· compte crÃ©Ã© le <?= e(date_fr($compte['cree_le'])) ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <form method="post" action="<?= e(url('administrateur/utilisateur/activer')) ?>"
              data-confirmer="<?= (int) $compte['actif'] === 1 ? 'DÃ©sactiver ce compte ?' : 'Activer ce compte ?' ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $compte['id'] ?>">
            <button type="submit" class="btn btn-outline-<?= (int) $compte['actif'] === 1 ? 'danger' : 'success' ?>">
                <i class="bi bi-<?= (int) $compte['actif'] === 1 ? 'pause-circle' : 'play-circle' ?> me-1"></i>
                <?= (int) $compte['actif'] === 1 ? 'DÃ©sactiver' : 'Activer' ?>
            </button>
        </form>
        <a href="<?= e(url('administrateur/utilisateurs')) ?>" class="btn btn-outline-secondary">Retour</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <form method="post" action="<?= e(url('administrateur/utilisateur/modifier')) ?>" class="card">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $compte['id'] ?>">
            <div class="card-header"><i class="bi bi-person-gear text-nma me-2"></i>Informations du compte</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="nom">Nom</label>
                        <input type="text" class="form-control" id="nom" name="nom" required maxlength="150"
                               value="<?= e($compte['nom']) ?>">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="email">Adresse e-mail</label>
                        <input type="email" class="form-control" id="email" name="email" required value="<?= e($compte['email']) ?>">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="telephone">TÃ©lÃ©phone</label>
                        <input type="tel" class="form-control" id="telephone" name="telephone" maxlength="30"
                               value="<?= e($compte['telephone'] ?? '') ?>">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="role">RÃ´le</label>
                        <select class="form-select" id="role" name="role" required>
                            <?php foreach (['boulanger', 'gestionnaire', 'administrateur'] as $role): ?>
                                <option value="<?= e($role) ?>" <?= $compte['role'] === $role ? 'selected' : '' ?>>
                                    <?= e(ucfirst($role)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="double_verification"
                                   name="double_verification" <?= (int) $compte['double_verification'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="double_verification">
                                Exiger une double vÃ©rification Ã  la connexion (recommandÃ© pour gestionnaire et administrateur)
                            </label>
                        </div>
                    </div>

                    <?php if ($estBoulanger): ?>
                        <div class="col-12"><hr class="my-1"></div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="nom_boulangerie">Nom de la boulangerie</label>
                            <input type="text" class="form-control" id="nom_boulangerie" name="nom_boulangerie" maxlength="190"
                                   value="<?= e($compte['nom_boulangerie'] ?? '') ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="ville">Ville / commune</label>
                            <input type="text" class="form-control" id="ville" name="ville" maxlength="100"
                                   value="<?= e($compte['boulanger_ville'] ?? 'Dakar') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="adresse">Adresse de livraison</label>
                            <textarea class="form-control" id="adresse" name="adresse" rows="2"
                                      maxlength="255"><?= e($compte['boulanger_adresse'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="zone_livraison">Zone de livraison</label>
                            <input type="text" class="form-control" id="zone_livraison" name="zone_livraison" maxlength="100"
                                   value="<?= e($compte['zone_livraison'] ?? '') ?>">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer bg-white d-flex gap-2">
                <button type="submit" class="btn btn-nma flex-grow-1"><i class="bi bi-save me-2"></i>Enregistrer</button>
                <button type="submit" formaction="<?= e(url('administrateur/utilisateur/mot-de-passe')) ?>"
                        class="btn btn-outline-secondary"
                        onclick="return confirm('GÃ©nÃ©rer un mot de passe provisoire pour ce compte ?');">
                    <i class="bi bi-key me-1"></i>RÃ©initialiser le mot de passe
                </button>
            </div>
        </form>
    </div>

    <div class="col-12 col-lg-5">
        <?php if ($estBoulanger && $commandes !== []): ?>
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-receipt text-nma me-2"></i>DerniÃ¨res commandes</div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($commandes as $c): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <a href="<?= e(url('gestionnaire/commande', ['id' => (int) $c['id']])) ?>" class="fw-semibold text-small">
                                        <?= e($c['reference']) ?>
                                    </a>
                                    <div class="text-small text-muted"><?= e(date_fr($c['date_commande'])) ?> Â· <?= (int) $c['total_sacs'] ?> sac(s)</div>
                                </div>
                                <?= statut_badge($c['statut']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><i class="bi bi-journal-text text-nma me-2"></i>DerniÃ¨res opÃ©rations de ce compte</div>
            <div class="card-body p-0">
                <?php if ($operations === []): ?>
                    <div class="nma-empty"><i class="bi bi-journal"></i>Aucune opÃ©ration enregistrÃ©e.</div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($operations as $op): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between gap-2">
                                    <span class="fw-semibold text-small"><?= e(action_fr((string) $op['action'])) ?></span>
                                    <span class="text-small text-muted text-nowrap"><?= e(date_fr($op['date_heure'], 'd/m H\hi')) ?></span>
                                </div>
                                <div class="text-small text-muted">
                                    <?= e($op['details'] ?? '') ?>
                                    <?php if (!empty($op['adresse_ip'])): ?> Â· <?= e($op['adresse_ip']) ?><?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

