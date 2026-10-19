<?php
/**
 * F17 : gestion des comptes (creation, activation, reinitialisation).
 * @var array  $utilisateurs
 * @var array  $stats
 * @var array  $demandes
 * @var string $role
 * @var string $q
 */
$roles = ['' => 'Tous les rôles', 'boulanger' => 'Boulangers', 'gestionnaire' => 'Gestionnaires', 'administrateur' => 'Administrateurs'];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-0">Gestion des utilisateurs</h1>
        <p class="text-muted small mb-0">
            <?= (int) $stats['total'] ?> compte(s) · <?= (int) $stats['actifs'] ?> actif(s) · <?= (int) $stats['inactifs'] ?> en attente d'activation
        </p>
    </div>
    <button class="btn btn-nma" data-bs-toggle="collapse" data-bs-target="#formNouveauCompte">
        <i class="bi bi-person-plus me-2"></i>Créer un compte
    </button>
</div>

<?php if ($demandes !== []): ?>
    <div class="alert alert-info">
        <i class="bi bi-key me-1"></i>
        <strong>Demande(s) de réinitialisation en cours :</strong>
        <?php foreach ($demandes as $d): ?>
            <div class="small">
                — <?= e($d['nom']) ?> (<?= e($d['email']) ?>, <?= e($d['telephone'] ?? 'sans téléphone') ?>)
                <form method="post" action="<?= e(url('administrateur/utilisateur/mot-de-passe')) ?>" class="d-inline"
                      data-confirmer="Générer un mot de passe provisoire pour ce compte ?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-link p-0">générer un mot de passe provisoire</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="collapse mb-3" id="formNouveauCompte">
    <div class="card border-nma">
        <div class="card-header bg-nma-soft"><i class="bi bi-person-plus text-nma me-2"></i>Nouveau compte</div>
        <form method="post" action="<?= e(url('administrateur/utilisateur/creer')) ?>">
            <?= csrf_field() ?>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="n_nom">Nom du responsable</label>
                        <input type="text" class="form-control" id="n_nom" name="nom" required maxlength="150">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="n_email">Adresse e-mail (identifiant)</label>
                        <input type="email" class="form-control" id="n_email" name="email" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="n_tel">Téléphone</label>
                        <input type="tel" class="form-control" id="n_tel" name="telephone" maxlength="30" placeholder="+221 77 000 00 00">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="n_role">Rôle</label>
                        <select class="form-select" id="n_role" name="role" required>
                            <option value="boulanger">Boulanger (client)</option>
                            <option value="gestionnaire">Gestionnaire (commercial)</option>
                            <option value="administrateur">Administrateur</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="n_mdp">Mot de passe initial</label>
                        <input type="text" class="form-control" id="n_mdp" name="mot_de_passe" required
                               placeholder="8 caractères, majuscules/minuscules/chiffres">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="n_mdp2">Confirmation</label>
                        <input type="text" class="form-control" id="n_mdp2" name="mot_de_passe_confirmation" required>
                    </div>

                    <div class="col-12"><hr class="my-1"></div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="n_boul">Nom de la boulangerie <span class="text-muted small">(boulanger)</span></label>
                        <input type="text" class="form-control" id="n_boul" name="nom_boulangerie" maxlength="190">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="n_adresse">Adresse de livraison <span class="text-muted small">(boulanger)</span></label>
                        <input type="text" class="form-control" id="n_adresse" name="adresse" maxlength="255">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label" for="n_ville">Ville</label>
                        <input type="text" class="form-control" id="n_ville" name="ville" maxlength="100" value="Dakar">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label" for="n_zone">Zone de livraison</label>
                        <input type="text" class="form-control" id="n_zone" name="zone_livraison" maxlength="100">
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" value="1" id="n_actif" name="actif" checked>
                            <label class="form-check-label" for="n_actif">Compte immédiatement actif</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="n_dv" name="double_verification">
                            <label class="form-check-label" for="n_dv">Exiger une double vérification à la connexion</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white">
                <button type="submit" class="btn btn-nma w-100"><i class="bi bi-check2 me-2"></i>Créer le compte</button>
            </div>
        </form>
    </div>
</div>

<form method="get" action="<?= e(url('administrateur/utilisateurs')) ?>" class="card mb-3">
    <input type="hidden" name="r" value="administrateur/utilisateurs">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label small" for="role">Rôle</label>
                <select class="form-select" id="role" name="role" data-filtre-auto>
                    <?php foreach ($roles as $cle => $libelle): ?>
                        <option value="<?= e($cle) ?>" <?= $role === $cle ? 'selected' : '' ?>><?= e($libelle) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-6">
                <label class="form-label small" for="q">Recherche</label>
                <input type="search" class="form-control" id="q" name="q" value="<?= e($q) ?>"
                       placeholder="Nom, e-mail, téléphone, boulangerie...">
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-nma flex-grow-1"><i class="bi bi-search"></i></button>
                <a href="<?= e(url('administrateur/utilisateurs')) ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <?php if ($utilisateurs === []): ?>
            <div class="nma-empty"><i class="bi bi-people"></i>Aucun compte ne correspond à ces critères.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th class="d-none d-md-table-cell">Contact</th>
                            <th>Rôle</th>
                            <th>État</th>
                            <th class="d-none d-lg-table-cell">Dernière connexion</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($utilisateurs as $u): ?>
                        <tr>
                            <td>
                                <a href="<?= e(url('administrateur/utilisateur', ['id' => (int) $u['id']])) ?>" class="fw-semibold">
                                    <?= e($u['nom']) ?>
                                </a>
                                <?php if (!empty($u['nom_boulangerie'])): ?>
                                    <div class="text-small text-muted"><i class="bi bi-shop me-1"></i><?= e($u['nom_boulangerie']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="d-none d-md-table-cell text-small">
                                <?= e($u['email']) ?>
                                <div class="text-muted"><?= e($u['telephone'] ?? '—') ?></div>
                            </td>
                            <td>
                                <span class="badge text-bg-<?= $u['role'] === 'administrateur' ? 'dark' : ($u['role'] === 'gestionnaire' ? 'info' : 'secondary') ?>">
                                    <?= e(ucfirst($u['role'])) ?>
                                </span>
                                <?php if ((int) $u['double_verification'] === 1): ?>
                                    <i class="bi bi-shield-lock text-success ms-1" title="Double vérification activée"></i>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge text-bg-<?= (int) $u['actif'] === 1 ? 'success' : 'secondary' ?>">
                                    <?= (int) $u['actif'] === 1 ? 'Actif' : 'Inactif' ?>
                                </span>
                                <?php if ($u['reset_token'] !== null && $u['reset_expire_le'] !== null && strtotime((string) $u['reset_expire_le']) > time()): ?>
                                    <span class="badge text-bg-warning">Demande mdp</span>
                                <?php endif; ?>
                            </td>
                            <td class="d-none d-lg-table-cell text-small text-muted"><?= e(datetime_fr($u['derniere_connexion'])) ?></td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="<?= e(url('administrateur/utilisateur', ['id' => (int) $u['id']])) ?>"
                                       class="btn btn-sm btn-outline-nma" title="Modifier"><i class="bi bi-pencil"></i></a>

                                    <form method="post" action="<?= e(url('administrateur/utilisateur/activer')) ?>"
                                          data-confirmer="<?= (int) $u['actif'] === 1 ? 'Désactiver ce compte ? L\'utilisateur ne pourra plus se connecter.' : 'Activer ce compte ?' ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-<?= (int) $u['actif'] === 1 ? 'danger' : 'success' ?>"
                                                title="<?= (int) $u['actif'] === 1 ? 'Désactiver' : 'Activer' ?>">
                                            <i class="bi bi-<?= (int) $u['actif'] === 1 ? 'pause-circle' : 'play-circle' ?>"></i>
                                        </button>
                                    </form>

                                    <form method="post" action="<?= e(url('administrateur/utilisateur/mot-de-passe')) ?>"
                                          data-confirmer="Générer un mot de passe provisoire pour <?= e($u['email']) ?> ?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Réinitialiser le mot de passe">
                                            <i class="bi bi-key"></i>
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
