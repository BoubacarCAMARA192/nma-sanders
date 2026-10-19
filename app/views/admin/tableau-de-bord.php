<?php
/**
 * Tableau de bord administrateur : comptes, catalogue, activite et journal.
 * @var array $users
 * @var array $produits
 * @var array $compteurs
 * @var array $stats
 * @var array $alertes
 * @var array $operations
 * @var array $aTraiter
 */
?>
<h1 class="h4 fw-bold mb-1">Administration de la plateforme</h1>
<p class="text-muted small mb-3">Comptes, catalogue, traÃ§abilitÃ© et paramÃ¨tres gÃ©nÃ©raux.</p>

<div class="row g-3 mb-4">
    <?php
    $cartes = [
        ['Comptes boulangers', $users['boulangers'],  'shop',        'nma'],
        ['Comptes actifs',     $users['actifs'],      'person-check','success'],
        ['Produits au catalogue', $produits['actifs'],'box-seam',    'info'],
        ['Commandes reÃ§ues',   $compteurs['recue'],   'inbox',       'warning'],
    ];
    foreach ($cartes as [$libelle, $valeur, $icone, $couleur]): ?>
        <div class="col-6 col-lg-3">
            <div class="nma-stat d-flex align-items-center gap-3">
                <div class="nma-stat-ico text-<?= e($couleur) ?>"><i class="bi bi-<?= e($icone) ?>"></i></div>
                <div>
                    <div class="nma-stat-value"><?= (int) $valeur ?></div>
                    <div class="nma-stat-label"><?= e($libelle) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-journal-text text-nma me-2"></i>DerniÃ¨res opÃ©rations</span>
                <a href="<?= e(url('administrateur/journal')) ?>" class="small">Journal complet</a>
            </div>
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
                                    <?= e(Core\Journal::auteur($op['auteur'] ?? null, $op['auteur_role'] ?? null)) ?>
                                    <?php if (!empty($op['details'])): ?> Â· <?= e($op['details']) ?><?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($alertes !== []): ?>
            <div class="card border-warning">
                <div class="card-header text-warning d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-exclamation-triangle me-2"></i>Stocks sous le seuil d'alerte</span>
                    <a href="<?= e(url('administrateur/catalogue')) ?>" class="small">GÃ©rer le stock</a>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($alertes as $p): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-small"><?= e($p['libelle']) ?></span>
                                <span class="badge text-bg-warning"><?= (int) $p['stock_disponible'] ?> / seuil <?= (int) $p['seuil_alerte'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-clipboard-data text-nma me-2"></i>ActivitÃ© des 3 derniers mois</div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Commandes</span><strong><?= (int) $stats['total'] ?></strong></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Sacs commandÃ©s</span><strong><?= (int) $stats['sacs'] ?></strong></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Taux de livraison</span><strong><?= e((string) $stats['taux_livraison']) ?> %</strong></div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">DÃ©lai moyen de livraison</span>
                    <strong><?= $stats['delai_moyen'] !== null ? e((string) $stats['delai_moyen']) . ' h' : 'â€”' ?></strong>
                </div>
                <div class="nma-sep"></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Comptes inactifs</span><strong><?= (int) $users['inactifs'] ?></strong></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Gestionnaires</span><strong><?= (int) $users['gestionnaires'] ?></strong></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Stock total (sacs)</span><strong><?= (int) $produits['stock'] ?></strong></div>
            </div>
            <div class="card-footer bg-white d-flex gap-2">
                <a href="<?= e(url('administrateur/statistiques')) ?>" class="btn btn-sm btn-outline-nma flex-grow-1">Statistiques</a>
                <a href="<?= e(url('administrateur/utilisateurs')) ?>" class="btn btn-sm btn-nma flex-grow-1">GÃ©rer les comptes</a>
            </div>
        </div>

        <?php if ($aTraiter !== []): ?>
            <div class="card">
                <div class="card-header"><i class="bi bi-inbox text-warning me-2"></i>Commandes en attente de validation</div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($aTraiter as $c): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold text-small"><?= e($c['reference']) ?></div>
                                    <div class="text-small text-muted"><?= e($c['nom_boulangerie']) ?> Â· <?= (int) $c['total_sacs'] ?> sac(s)</div>
                                </div>
                                <a href="<?= e(url('gestionnaire/commande', ['id' => (int) $c['id']])) ?>" class="btn btn-sm btn-outline-nma">Ouvrir</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

