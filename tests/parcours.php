<?php
/**
 * Test fonctionnel de bout en bout : parcours boulanger, gestionnaire, administrateur.
 */

define('BASE', getenv('SC_TEST_BASE') ?: 'http://localhost/sanders-connect/index.php');

/** Hote reel du serveur teste (les redirections absolues y sont resolues). */
function base_hote(): string
{
    $parties = parse_url(BASE);
    $hote = ($parties['scheme'] ?? 'http') . '://' . ($parties['host'] ?? 'localhost');

    return isset($parties['port']) ? $hote . ':' . $parties['port'] : $hote;
}

/** Acces direct a la base pour les verifications (MySQL local ou SQLite de test). */
function pdo_test(): PDO
{
    if (getenv('SC_TEST_SQLITE') !== false) {
        $pdo = new PDO('sqlite:' . rtrim(sys_get_temp_dir(), '/\\') . '/sanders_connect.sqlite');
        $pdo->exec('PRAGMA busy_timeout = 10000');

        return $pdo;
    }

    return new PDO('mysql:host=127.0.0.1;dbname=sanders_connect', 'root', '');
}
$COOKIES = sys_get_temp_dir() . '/sc_test_cookies.txt';
@unlink($COOKIES);

$succes = 0;
$echecs = 0;

function requete(string $url, ?array $post = null, string $cookies = ''): array
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $cookies,
        CURLOPT_COOKIEFILE => $cookies,
        CURLOPT_TIMEOUT => 30,
    ]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $reponse = curl_exec($ch);
    if ($reponse === false) {
        return ['code' => 0, 'corps' => 'ERREUR: ' . curl_error($ch), 'location' => null];
    }
    $taille = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $entetes = substr($reponse, 0, $taille);
    $corps = substr($reponse, $taille);
    $location = null;
    if (preg_match('/^Location:\s*(.+)$/mi', $entetes, $m)) {
        $location = trim($m[1]);
    }
    return ['code' => curl_getinfo($ch, CURLINFO_HTTP_CODE), 'corps' => $corps, 'location' => $location];
}

function naviguer(string $url, string $cookies): array
{
    for ($i = 0; $i < 6; $i++) {
        $r = requete($url, null, $cookies);
        if ($r['location'] === null) {
            return $r;
        }
        $url = str_starts_with($r['location'], '/') ? base_hote() . $r['location'] : $r['location'];
    }
    return ['code' => 0, 'corps' => 'BOUCLE DE REDIRECTION', 'location' => null];
}

function token(string $html): string
{
    return preg_match('/name="_token" value="([^"]+)"/', $html, $m) ? $m[1] : '';
}

function verifier(string $libelle, bool $condition, string $detail = ''): void
{
    global $succes, $echecs;
    if ($condition) {
        $succes++;
        echo "  [OK]    $libelle" . ($detail !== '' ? " — $detail" : '') . PHP_EOL;
    } else {
        $echecs++;
        echo "  [ECHEC] $libelle" . ($detail !== '' ? " — $detail" : '') . PHP_EOL;
    }
}

function ouvrirSession(string $email, string $motDePasse, string $cookies): array
{
    @unlink($cookies);
    $page = naviguer(BASE . '?r=connexion', $cookies);
    $r = requete(BASE . '?r=connexion', ['_token' => token($page['corps']), 'email' => $email, 'mot_de_passe' => $motDePasse], $cookies);
    if ($r['location'] !== null) {
        return naviguer(str_starts_with($r['location'], '/') ? base_hote() . $r['location'] : $r['location'], $cookies);
    }
    return naviguer(BASE . '?r=connexion', $cookies);
}

echo "===================================================================" . PHP_EOL;
echo " TESTS FONCTIONNELS — PLATEFORME SANDERS CONNECT" . PHP_EOL;
echo "===================================================================" . PHP_EOL;

// ---------------------------------------------------------------- 1. Securite
echo PHP_EOL . "== 1. Contrôle d'accès et sécurité ==" . PHP_EOL;
$r = naviguer(BASE . '?r=boulanger/tableau-de-bord', $COOKIES);
verifier('Accès boulanger refusé sans connexion', str_contains($r['corps'], 'Connexion'));

$r = naviguer(BASE . '?r=route/inexistante', $COOKIES);
verifier('Route inconnue → 404', $r['code'] === 404, 'code ' . $r['code']);

$page = naviguer(BASE . '?r=connexion', $COOKIES);
$jeton = token($page['corps']);
$r = requete(BASE . '?r=connexion', ['email' => 'x@y.z', 'mot_de_passe' => 'x'], $COOKIES);
verifier('POST sans jeton CSRF rejeté (403)', $r['code'] === 403, 'code ' . $r['code']);

$r = requete(BASE . '?r=connexion', ['_token' => $jeton, 'email' => 'inexistant@test.sn', 'mot_de_passe' => 'MauvaisPass1'], $COOKIES);
verifier('Identifiants incorrects refusés', str_contains($r['corps'], 'incorrects'), 'code ' . $r['code']);

$r = requete(BASE . '?r=connexion', ['_token' => $jeton, 'email' => 'pas-un-email', 'mot_de_passe' => 'MauvaisPass1'], $COOKIES);
verifier('Adresse e-mail invalide rejetée', str_contains($r['corps'], 'adresse e-mail'));

$r = naviguer(BASE . '?r=boulanger/tableau-de-bord', $COOKIES);
verifier('Aucune fuite de données sans session', !str_contains($r['corps'], 'Boulangerie'));

// ---------------------------------------------------------------- 2. Boulanger
echo PHP_EOL . "== 2. Parcours boulanger ==" . PHP_EOL;
$tableau = ouvrirSession('boulanger@sandersconnect.sn', 'Boulanger@2026', $COOKIES);
verifier('Connexion boulanger réussie', str_contains($tableau['corps'], 'Bonjour'), 'taille ' . strlen($tableau['corps']));

$catalogue = naviguer(BASE . '?r=boulanger/catalogue', $COOKIES);
verifier('Catalogue affiché (5 farines Sanders Connect)', substr_count($catalogue['corps'], 'Ajouter') >= 5);

$produitsIds = [];
if (preg_match_all('/name="produit_id" value="(\d+)"/', $catalogue['corps'], $m)) {
    $produitsIds = array_values(array_unique($m[1]));
}
verifier('Produits disponibles détectés', count($produitsIds) >= 4, count($produitsIds) . ' produit(s)');

$jetonCat = token($catalogue['corps']);
if (isset($produitsIds[0])) {
    requete(BASE . '?r=boulanger/panier/ajouter', ['_token' => $jetonCat, 'produit_id' => $produitsIds[0], 'quantite' => '10', 'retour' => 'boulanger/catalogue'], $COOKIES);
}
if (isset($produitsIds[1])) {
    requete(BASE . '?r=boulanger/panier/ajouter', ['_token' => $jetonCat, 'produit_id' => $produitsIds[1], 'quantite' => '5', 'retour' => 'boulanger/catalogue'], $COOKIES);
}
$panier = naviguer(BASE . '?r=boulanger/commande/nouvelle', $COOKIES);
verifier('Panier avec 2 lignes', substr_count($panier['corps'], 'name="produit_id"') >= 2);

$r = requete(BASE . '?r=boulanger/commande/enregistrer', [
    '_token' => token($panier['corps']),
    'date_livraison_souhaitee' => date('Y-m-d', strtotime('+3 days')),
    'adresse_livraison' => 'Rue 12 x Avenue Bourguiba, Dakar',
    'commentaire' => 'Test automatisé : livrer avant 10 h.',
    'mode_paiement' => 'wave',
], $COOKIES);
verifier('Commande enregistrée (redirection)', $r['location'] !== null, $r['location'] ?? '');

$suivi = naviguer($r['location'] !== null ? base_hote() . $r['location'] : BASE . '?r=boulanger/historique', $COOKIES);
verifier('Suivi de commande affiché', str_contains($suivi['corps'], 'Commande reçue'));
verifier('Mode de paiement enregistré (Wave)', str_contains($suivi['corps'], 'Wave'));
preg_match('/CMD-\d{8}-[A-Z0-9]{5}/', $suivi['corps'], $ref);
$reference = $ref[0] ?? '';
verifier('Référence de commande générée', $reference !== '', $reference);

preg_match('/name="id" value="(\d+)"/', $suivi['corps'], $mid);
$commandeId = (int) ($mid[1] ?? 0);

// Date trop proche : doit etre refusee (delai minimum 24 h)
$r = requete(BASE . '?r=boulanger/commande/modifier', [
    '_token' => token($suivi['corps']),
    'id' => $commandeId,
    'date_livraison_souhaitee' => date('Y-m-d'),
    'adresse_livraison' => 'Rue 12, Dakar',
    'quantites' => [$produitsIds[0] => 3],
], $COOKIES);
$suivi2 = naviguer(BASE . '?r=boulanger/commande&id=' . $commandeId, $COOKIES);
verifier('Date de livraison trop proche refusée (délai minimum)', str_contains($suivi2['corps'], 'au plus tôt le'));

$historique = naviguer(BASE . '?r=boulanger/historique', $COOKIES);
verifier('Historique accessible', str_contains($historique['corps'], $reference));

$modif = requete(BASE . '?r=boulanger/commande/modifier', [
    '_token' => token($suivi['corps']),
    'id' => $commandeId,
    'date_livraison_souhaitee' => date('Y-m-d', strtotime('+4 days')),
    'adresse_livraison' => 'Rue 12 x Avenue Bourguiba, Dakar (modifiée)',
    'commentaire' => 'Commande modifiée par le test.',
    'mode_paiement' => 'orange_money',
    'quantites' => array_combine($produitsIds, array_fill(0, count($produitsIds), 8)),
], $COOKIES);
$suivi2 = naviguer(BASE . '?r=boulanger/commande&id=' . $commandeId, $COOKIES);
verifier('Commande modifiée par le boulanger', str_contains($suivi2['corps'], 'modifiée par le test'));
verifier('Mode de paiement modifié (Orange Money)', str_contains($suivi2['corps'], 'Orange Money'));

$r = naviguer(BASE . '?r=boulanger/commande&id=999999', $COOKIES);
verifier('Commande inexistante → accès bloqué', str_contains($r['corps'], 'introuvable') || $r['code'] === 404);

$r = naviguer(BASE . '?r=administrateur/utilisateurs', $COOKIES);
verifier('Boulanger interdit sur l\'espace admin (403)', $r['code'] === 403, 'code ' . $r['code']);

$r = naviguer(BASE . '?r=gestionnaire/commandes', $COOKIES);
verifier('Boulanger interdit sur l\'espace gestionnaire (403)', $r['code'] === 403, 'code ' . $r['code']);

$r = naviguer(BASE . '?r=boulanger/commande&id=1&injection=%27%20OR%201%3D1--', $COOKIES);
verifier('Tentative d\'injection SQL neutralisée', $r['code'] === 200 || $r['code'] === 302, 'code ' . $r['code']);

// ---------------------------------------------------------------- 3. Gestionnaire
echo PHP_EOL . "== 3. Parcours gestionnaire ==" . PHP_EOL;
$gd = ouvrirSession('gestionnaire@sandersconnect.sn', 'Gestion@2026', $COOKIES);
verifier('Connexion gestionnaire réussie', str_contains($gd['corps'], 'Pilotage des commandes'));

$liste = naviguer(BASE . '?r=gestionnaire/commandes', $COOKIES);
verifier('Commande du boulanger visible chez le gestionnaire', str_contains($liste['corps'], $reference), $reference);
$r = naviguer(BASE . '?r=gestionnaire/commandes&statut=recue', $COOKIES);
verifier('Filtre par statut « reçue »', str_contains($r['corps'], $reference));

$notifs = naviguer(BASE . '?r=notifications', $COOKIES);
verifier('Notification de nouvelle commande reçue (F09)', str_contains($notifs['corps'], $reference) || str_contains($notifs['corps'], 'Nouvelle commande'));

$detail = naviguer(BASE . '?r=gestionnaire/commande&id=' . $commandeId, $COOKIES);
verifier('Détail de la commande accessible', str_contains($detail['corps'], 'Décision à prendre'));

$r = requete(BASE . '?r=gestionnaire/commande/refuser', ['_token' => token($detail['corps']), 'id' => $commandeId, 'motif_refus' => ''], $COOKIES);
$detail = naviguer(BASE . '?r=gestionnaire/commande&id=' . $commandeId, $COOKIES);
verifier('Refus sans motif rejeté', str_contains($detail['corps'], 'motif du refus est obligatoire') || str_contains($detail['corps'], 'obligatoire'));

$r = requete(BASE . '?r=gestionnaire/commande/valider', ['_token' => token($detail['corps']), 'id' => $commandeId], $COOKIES);
$detail = naviguer(BASE . '?r=gestionnaire/commande&id=' . $commandeId, $COOKIES);
verifier('Commande validée', str_contains($detail['corps'], 'Validée'));

$stockAvant = (int) pdo_test()->query('SELECT stock_disponible FROM produit WHERE id = ' . (int) $produitsIds[0])->fetchColumn();
echo "        (stock restant du 1er produit : $stockAvant)" . PHP_EOL;

$r = requete(BASE . '?r=gestionnaire/commande/valider', ['_token' => token($detail['corps']), 'id' => $commandeId], $COOKIES);
$detail = naviguer(BASE . '?r=gestionnaire/commande&id=' . $commandeId, $COOKIES);
verifier('Double validation refusée', str_contains($detail['corps'], 'Seule une commande'));

$r = requete(BASE . '?r=gestionnaire/commande/statut', ['_token' => token($detail['corps']), 'id' => $commandeId, 'statut' => 'en_preparation', 'note' => 'Préparation test'], $COOKIES);
$detail = naviguer(BASE . '?r=gestionnaire/commande&id=' . $commandeId, $COOKIES);
verifier('Passage en préparation', str_contains($detail['corps'], 'Préparation test'));

$r = requete(BASE . '?r=gestionnaire/commande/statut', ['_token' => token($detail['corps']), 'id' => $commandeId, 'statut' => 'livree'], $COOKIES);
$detail = naviguer(BASE . '?r=gestionnaire/commande&id=' . $commandeId, $COOKIES);
verifier('Saut de statut interdit (séquence respectée)', str_contains($detail['corps'], 'non autorisé'));

$r = requete(BASE . '?r=gestionnaire/livraisons/planifier', [
    '_token' => token($detail['corps']),
    'commande_id' => $commandeId,
    'date_livraison' => date('Y-m-d', strtotime('+1 day')),
    'livreur' => 'Moussa Test',
    'vehicule' => 'Camion DK-1234-AB',
    'commentaire' => 'Tournée test',
], $COOKIES);
$detail = naviguer(BASE . '?r=gestionnaire/commande&id=' . $commandeId, $COOKIES);
verifier('Livraison planifiée', str_contains($detail['corps'], 'Moussa Test'));

$r = requete(BASE . '?r=gestionnaire/commande/statut', ['_token' => token($detail['corps']), 'id' => $commandeId, 'statut' => 'en_livraison'], $COOKIES);
$detail = naviguer(BASE . '?r=gestionnaire/commande&id=' . $commandeId, $COOKIES);
$r = requete(BASE . '?r=gestionnaire/livraisons/etat', ['_token' => token($detail['corps']), 'commande_id' => $commandeId, 'etat' => 'livree'], $COOKIES);
$detail = naviguer(BASE . '?r=gestionnaire/commande&id=' . $commandeId, $COOKIES);
verifier('Commande livrée (fin de séquence)', str_contains($detail['corps'], 'Livrée'));

$pageNotifs = naviguer(BASE . '?r=notifications', $COOKIES);
$r = requete(BASE . '?r=gestionnaire/commande/statut', ['_token' => token($pageNotifs['corps']), 'id' => $commandeId, 'statut' => 'en_livraison'], $COOKIES);
$detail = naviguer(BASE . '?r=gestionnaire/commande&id=' . $commandeId, $COOKIES);
verifier('Statut figé après livraison (protection serveur)', str_contains($detail['corps'], 'terminée ou interrompue'));

$planning = naviguer(BASE . '?r=gestionnaire/livraisons', $COOKIES);
verifier('Planning des livraisons accessible', str_contains($planning['corps'], 'Planning des livraisons'));

$stats = naviguer(BASE . '?r=gestionnaire/statistiques', $COOKIES);
verifier('Statistiques gestionnaire', str_contains($stats['corps'], 'Délai moyen'));

$csv = naviguer(BASE . '?r=gestionnaire/export', $COOKIES);
verifier('Export CSV généré (compatible CRM/SAP)', str_contains($csv['corps'], 'Reference') && str_contains($csv['corps'], $reference));

$r = naviguer(BASE . '?r=gestionnaire/export&statut=non_valide&depuis=2026-13-45', $COOKIES);
verifier('Export avec filtres invalides : aucune erreur', $r['code'] === 200, 'code ' . $r['code']);

$clients = naviguer(BASE . '?r=gestionnaire/boulangers', $COOKIES);
verifier('Fiche clients boulangers', str_contains($clients['corps'], 'Boulangerie Demo'));

// ---------------------------------------------------------------- 4. Administrateur
echo PHP_EOL . "== 4. Parcours administrateur ==" . PHP_EOL;
$adm = ouvrirSession('admin@sandersconnect.sn', 'Admin@2026', $COOKIES);
verifier('Connexion administrateur réussie', str_contains($adm['corps'], 'Administration de la plateforme'));

$users = naviguer(BASE . '?r=administrateur/utilisateurs', $COOKIES);
verifier('Liste des utilisateurs', str_contains($users['corps'], 'boulanger@sandersconnect.sn'));

$emailTest = 'test.auto' . random_int(100, 999) . '@boulangerie.sn';
$r = requete(BASE . '?r=administrateur/utilisateur/creer', [
    '_token' => token($users['corps']),
    'nom' => 'Boulanger Test Auto',
    'email' => $emailTest,
    'telephone' => '+221 77 000 11 22',
    'role' => 'boulanger',
    'mot_de_passe' => 'Test@2026',
    'mot_de_passe_confirmation' => 'Test@2026',
    'actif' => '1',
    'nom_boulangerie' => 'Boulangerie Test Auto',
    'adresse' => 'Zone de captage, Thiès',
    'ville' => 'Thiès',
    'zone_livraison' => 'Thiès',
], $COOKIES);
$users = naviguer(BASE . '?r=administrateur/utilisateurs', $COOKIES);
verifier('Compte boulanger créé (actif)', str_contains($users['corps'], $emailTest));

$pdo = pdo_test();
$stmt = $pdo->prepare('SELECT u.id, u.actif, u.role, b.nom_boulangerie FROM utilisateur u LEFT JOIN boulanger b ON b.utilisateur_id = u.id WHERE u.email = ?');
$stmt->execute([$emailTest]);
$nouveau = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();
verifier('Fiche boulanger créée automatiquement', $nouveau !== false && $nouveau['nom_boulangerie'] === 'Boulangerie Test Auto', '#' . ($nouveau['id'] ?? '?'));
$nouvelId = (int) ($nouveau['id'] ?? 0);

$r = requete(BASE . '?r=administrateur/utilisateur/creer', [
    '_token' => token($users['corps']),
    'nom' => 'Doublon',
    'email' => $emailTest,
    'role' => 'boulanger',
    'mot_de_passe' => 'Test@2026',
    'mot_de_passe_confirmation' => 'Test@2026',
    'nom_boulangerie' => 'X',
    'adresse' => 'Y',
], $COOKIES);
$users = naviguer(BASE . '?r=administrateur/utilisateurs', $COOKIES);
verifier('Doublon d\'adresse e-mail refusé', str_contains($users['corps'], 'déjà utilisée'));

$r = requete(BASE . '?r=administrateur/utilisateur/creer', [
    '_token' => token($users['corps']),
    'nom' => 'Mot de passe faible',
    'email' => 'faible' . random_int(100, 999) . '@test.sn',
    'role' => 'boulanger',
    'mot_de_passe' => 'abc',
    'mot_de_passe_confirmation' => 'abc',
    'nom_boulangerie' => 'X',
    'adresse' => 'Y',
], $COOKIES);
$users = naviguer(BASE . '?r=administrateur/utilisateurs', $COOKIES);
verifier('Mot de passe faible refusé', str_contains($users['corps'], 'au moins 8 caractères'));

// Desactivation puis tentative de connexion avec une session vierge
$r = requete(BASE . '?r=administrateur/utilisateur/activer', ['_token' => token($users['corps']), 'id' => $nouvelId], $COOKIES);
$cookiesNeufs = sys_get_temp_dir() . '/sc_cookies_neufs.txt';
@unlink($cookiesNeufs);
$formulaire = naviguer(BASE . '?r=connexion', $cookiesNeufs);
$r = requete(BASE . '?r=connexion', ['_token' => token($formulaire['corps']), 'email' => $emailTest, 'mot_de_passe' => 'Test@2026'], $cookiesNeufs);
verifier('Connexion refusée pour un compte désactivé', str_contains($r['corps'], 'non activé'), 'code ' . $r['code']);

$adm2 = ouvrirSession('admin@sandersconnect.sn', 'Admin@2026', $COOKIES);
$users = naviguer(BASE . '?r=administrateur/utilisateurs', $COOKIES);
requete(BASE . '?r=administrateur/utilisateur/activer', ['_token' => token($users['corps']), 'id' => $nouvelId], $COOKIES);
$users = naviguer(BASE . '?r=administrateur/utilisateurs', $COOKIES);
$pdo->prepare('SELECT actif FROM utilisateur WHERE id = ?')->execute([$nouvelId]);
verifier('Compte réactivé par l\'administrateur', (int) $pdo->query('SELECT actif FROM utilisateur WHERE id = ' . $nouvelId)->fetchColumn() === 1);

$r = requete(BASE . '?r=administrateur/utilisateur/mot-de-passe', ['_token' => token($users['corps']), 'id' => $nouvelId], $COOKIES);
verifier('Réinitialisation de mot de passe par l\'administrateur', $r['location'] !== null);

// Catalogue
$catalogue = naviguer(BASE . '?r=administrateur/catalogue', $COOKIES);
$r = requete(BASE . '?r=administrateur/catalogue/enregistrer', [
    '_token' => token($catalogue['corps']),
    'id' => '0',
    'reference' => 'TEST-REF-01',
    'libelle' => 'Farine de test automatisé',
    'description' => 'Produit créé par le test',
    'image' => 'assets/img/produits/farine-boulangere.png',
    'unite' => 'sac de 50 kg',
    'prix_unitaire' => '21000',
    'stock_disponible' => '150',
    'seuil_alerte' => '25',
    'actif' => '1',
], $COOKIES);
$catalogue = naviguer(BASE . '?r=administrateur/catalogue', $COOKIES);
verifier('Produit créé au catalogue', str_contains($catalogue['corps'], 'TEST-REF-01'));

$produitTestId = (int) $pdo->query("SELECT id FROM produit WHERE reference = 'TEST-REF-01'")->fetchColumn();
verifier('Produit présent en base', $produitTestId > 0, '#' . $produitTestId);

$imageEnBase = (string) $pdo->query("SELECT image FROM produit WHERE id = $produitTestId")->fetchColumn();
verifier('Visuel produit enregistré', $imageEnBase === 'assets/img/produits/farine-boulangere.png', $imageEnBase);

requete(BASE . '?r=administrateur/catalogue/stock', ['_token' => token($catalogue['corps']), 'id' => $produitTestId, 'delta' => '40'], $COOKIES);
$catalogue = naviguer(BASE . '?r=administrateur/catalogue', $COOKIES);
$stockProduitTest = (int) $pdo->query("SELECT stock_disponible FROM produit WHERE id = $produitTestId")->fetchColumn();
verifier('Stock ajusté (+40 → 190)', $stockProduitTest === 190, (string) $stockProduitTest);

requete(BASE . '?r=administrateur/catalogue/stock', ['_token' => token($catalogue['corps']), 'id' => $produitTestId, 'delta' => '-1000'], $COOKIES);
$catalogue = naviguer(BASE . '?r=administrateur/catalogue', $COOKIES);
$stockProduitTest = (int) $pdo->query("SELECT stock_disponible FROM produit WHERE id = $produitTestId")->fetchColumn();
verifier('Stock jamais négatif (borné à 0)', $stockProduitTest === 0, (string) $stockProduitTest);

requete(BASE . '?r=administrateur/catalogue/activer', ['_token' => token($catalogue['corps']), 'id' => $produitTestId], $COOKIES);
$catalogue = naviguer(BASE . '?r=administrateur/catalogue', $COOKIES);
$actif = (int) $pdo->query("SELECT actif FROM produit WHERE id = $produitTestId")->fetchColumn();
verifier('Produit retiré du catalogue boulanger', $actif === 0);

// Verifier que le produit retire n'apparait plus au catalogue boulanger
$pageB = ouvrirSession('boulanger@sandersconnect.sn', 'Boulanger@2026', $COOKIES);
$catB = naviguer(BASE . '?r=boulanger/catalogue', $COOKIES);
verifier('Produit inactif absent du catalogue boulanger', !str_contains($catB['corps'], 'TEST-REF-01'));

$journal = ouvrirSession('admin@sandersconnect.sn', 'Admin@2026', $COOKIES);
$journal = naviguer(BASE . '?r=administrateur/journal', $COOKIES);
verifier('Journal des opérations consultable', str_contains($journal['corps'], 'Utilisateur créé') || str_contains($journal['corps'], 'Commande validée'));
verifier('Journal : périmètre complet tracé', str_contains($journal['corps'], 'Produit créé') && str_contains($journal['corps'], 'Stock ajusté'));

$params = naviguer(BASE . '?r=administrateur/parametres', $COOKIES);
verifier('Paramètres consultables', str_contains($params['corps'], 'Délai minimum de livraison'));

$r = requete(BASE . '?r=administrateur/parametres', ['_token' => token($params['corps']), 'delai_minimum_heures' => '48', 'max_tentatives_connexion' => '5', 'duree_blocage_minutes' => '15', 'session_duree_minutes' => '45', 'jours_historique_par_defaut' => '90'], $COOKIES);
$params = naviguer(BASE . '?r=administrateur/parametres', $COOKIES);
verifier('Paramètre « délai minimum » modifié à 48 h', str_contains($params['corps'], 'value="48"'));

$r = requete(BASE . '?r=administrateur/parametres', ['_token' => token($params['corps']), 'delai_minimum_heures' => '999999'], $COOKIES);
$params = naviguer(BASE . '?r=administrateur/parametres', $COOKIES);
verifier('Valeur de paramètre hors bornes rejetée', str_contains($params['corps'], 'compris entre'));

// Effet du parametre cote boulanger (48 h)
$pageB = ouvrirSession('boulanger@sandersconnect.sn', 'Boulanger@2026', $COOKIES);
$catB = naviguer(BASE . '?r=boulanger/catalogue', $COOKIES);
requete(BASE . '?r=boulanger/panier/ajouter', ['_token' => token($catB['corps']), 'produit_id' => $produitsIds[0], 'quantite' => '2', 'retour' => 'boulanger/commande/nouvelle'], $COOKIES);
$panier = naviguer(BASE . '?r=boulanger/commande/nouvelle', $COOKIES);
$r = requete(BASE . '?r=boulanger/commande/enregistrer', [
    '_token' => token($panier['corps']),
    'date_livraison_souhaitee' => date('Y-m-d', strtotime('+1 day')),
    'adresse_livraison' => 'Test délai minimum 48 h',
], $COOKIES);
$apres = naviguer(BASE . '?r=boulanger/commande/nouvelle', $COOKIES);
verifier('Délai minimum de 48 h appliqué au boulanger', str_contains($apres['corps'], date('d/m/Y', strtotime('+2 days'))), 'attendu ' . date('d/m/Y', strtotime('+2 days')));

// Restauration
$adm3 = ouvrirSession('admin@sandersconnect.sn', 'Admin@2026', $COOKIES);
$params = naviguer(BASE . '?r=administrateur/parametres', $COOKIES);
requete(BASE . '?r=administrateur/parametres', ['_token' => token($params['corps']), 'delai_minimum_heures' => '24', 'max_tentatives_connexion' => '5', 'duree_blocage_minutes' => '15', 'session_duree_minutes' => '45', 'jours_historique_par_defaut' => '90'], $COOKIES);

// ---------------------------------------------------------------- 5. Boulanger : tracabilite et fin
echo PHP_EOL . "== 5. Traçabilité, notifications et profil ==" . PHP_EOL;
$notifs = ouvrirSession('boulanger@sandersconnect.sn', 'Boulanger@2026', $COOKIES);
$notifs = naviguer(BASE . '?r=notifications', $COOKIES);
verifier('Boulanger informé des changements de statut (F14)', str_contains($notifs['corps'], $reference));

$suivi = naviguer(BASE . '?r=boulanger/commande&id=' . $commandeId, $COOKIES);
verifier('Frise de suivi horodatée complète', str_contains($suivi['corps'], 'Commande reçue')
    && str_contains($suivi['corps'], 'Commande validée')
    && str_contains($suivi['corps'], 'Livrée'));
verifier('Auteur et horodatage affichés', str_contains($suivi['corps'], 'Moussa Ndiaye'));
verifier('Commande livrée non modifiable par le boulanger', !str_contains($suivi['corps'], 'Commande encore modifiable'));

$profil = naviguer(BASE . '?r=boulanger/profil', $COOKIES);
verifier('Profil boulanger accessible', str_contains($profil['corps'], 'Boulangerie Demo'));
$r = requete(BASE . '?r=boulanger/profil/mot-de-passe', [
    '_token' => token($profil['corps']),
    'mot_de_passe_actuel' => 'MauvaisMotDePasse1',
    'nouveau_mot_de_passe' => 'Nouveau@2026',
    'nouveau_mot_de_passe_confirmation' => 'Nouveau@2026',
], $COOKIES);
$profil = naviguer(BASE . '?r=boulanger/profil', $COOKIES);
verifier('Changement de mot de passe refusé si ancien incorrect', str_contains($profil['corps'], 'incorrect'));

$deconnexion = naviguer(BASE . '?r=deconnexion&_token=' . urlencode(token($profil['corps'])), $COOKIES);
verifier('Déconnexion fonctionnelle', str_contains($deconnexion['corps'], 'Connexion'));
$r = naviguer(BASE . '?r=boulanger/tableau-de-bord', $COOKIES);
verifier('Session fermée après déconnexion', str_contains($r['corps'], 'Connexion'));

// ---------------------------------------------------------------- 6. Cloisonnement des donnees
echo PHP_EOL . "== 6. Cloisonnement des données ==" . PHP_EOL;
$pdo->exec("INSERT INTO utilisateur (nom, email, telephone, mot_de_passe, role, actif) VALUES ('Autre Boulanger', 'autre@test.sn', '+221 78 111 22 33', '" . password_hash('Autre@2026', PASSWORD_BCRYPT) . "', 'boulanger', 1)");
$autreId = (int) $pdo->lastInsertId();
$pdo->exec("INSERT INTO boulanger (utilisateur_id, nom_boulangerie, adresse, ville) VALUES ($autreId, 'Boulangerie Autre', 'Pikine', 'Dakar')");
$autreBoulangerId = (int) $pdo->lastInsertId();

$autre = ouvrirSession('autre@test.sn', 'Autre@2026', $COOKIES);
$r = naviguer(BASE . '?r=boulanger/commande&id=' . $commandeId, $COOKIES);
verifier('Un boulanger ne voit pas la commande d\'un autre', !str_contains($r['corps'], $reference) && (str_contains($r['corps'], 'introuvable') || $r['code'] === 404));

$r = naviguer(BASE . '?r=boulanger/historique', $COOKIES);
verifier('Historique vide pour le nouveau compte', str_contains($r['corps'], 'Aucune commande'));

// ---------------------------------------------------------------- 7. Inscription
echo PHP_EOL . "== 7. Inscription autonome ==" . PHP_EOL;
$cookiesInscription = sys_get_temp_dir() . '/sc_test_cookies_inscription.txt';
@unlink($cookiesInscription);

$accueilPub = naviguer(BASE . '?r=', $cookiesInscription);
verifier('Accueil public : choix inscription / connexion', str_contains($accueilPub['corps'], 'Créer un compte') && str_contains($accueilPub['corps'], 'Se connecter'));

$formInscription = naviguer(BASE . '?r=inscription', $cookiesInscription);
verifier('Formulaire d\'inscription accessible', str_contains($formInscription['corps'], 'name="nom_boulangerie"'));

$r = requete(BASE . '?r=inscription', ['_token' => token($formInscription['corps']), 'email' => 'incomplet@test.sn'], $cookiesInscription);
verifier('Inscription incomplète rejetée', str_contains($r['corps'], 'obligatoire'));

$emailInscription = 'demande.compte' . random_int(100, 999) . '@test.sn';
$r = requete(BASE . '?r=inscription', [
    '_token'                  => token($formInscription['corps']),
    'nom'                     => 'Awa Diop',
    'email'                   => $emailInscription,
    'telephone'               => '+221 77 555 44 33',
    'nom_boulangerie'         => 'Croissants du Plateau',
    'ville'                   => 'Dakar',
    'zone_livraison'          => 'Plateau',
    'mot_de_passe'            => 'Inscription@2026',
    'mot_de_passe_confirmation' => 'Inscription@2026',
], $cookiesInscription);

$suiviInscription = $r['location'] !== null
    ? naviguer(base_hote() . $r['location'], $cookiesInscription)
    : naviguer(BASE . '?r=inscription', $cookiesInscription);
verifier('Demande d\'inscription transmise (redirection connexion)', $r['location'] !== null && str_contains($r['location'], 'connexion'));
verifier('Confirmation « service commercial » affichée', str_contains($suiviInscription['corps'], 'service commercial'));

$stmt = $pdo->prepare('SELECT actif, role FROM utilisateur WHERE email = ?');
$stmt->execute([$emailInscription]);
$nouveauCompte = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();
verifier('Compte créé inactif en attente d\'activation', $nouveauCompte !== false && (int) $nouveauCompte['actif'] === 0 && $nouveauCompte['role'] === 'boulanger');

$pageConnexion = naviguer(BASE . '?r=connexion', $cookiesInscription);
$r = requete(BASE . '?r=connexion', ['_token' => token($pageConnexion['corps']), 'email' => $emailInscription, 'mot_de_passe' => 'Inscription@2026'], $cookiesInscription);
verifier('Connexion refusée tant que le compte n\'est pas activé', str_contains($r['corps'], 'non activé'));

$formDoublon = naviguer(BASE . '?r=inscription', $cookiesInscription);
$r = requete(BASE . '?r=inscription', [
    '_token'                  => token($formDoublon['corps']),
    'nom'                     => 'Awa Diop',
    'email'                   => $emailInscription,
    'telephone'               => '+221 77 555 44 33',
    'nom_boulangerie'         => 'Croissants du Plateau',
    'ville'                   => 'Dakar',
    'mot_de_passe'            => 'Inscription@2026',
    'mot_de_passe_confirmation' => 'Inscription@2026',
], $cookiesInscription);
verifier('Doublon d\'adresse e-mail refusé', str_contains($r['corps'], 'existe déjà'));

// Nettoyage des donnees de test
$pdo->exec("DELETE FROM utilisateur WHERE email IN ('$emailTest', 'autre@test.sn', '$emailInscription')");
$pdo->exec("DELETE FROM produit WHERE reference = 'TEST-REF-01'");
$pdo->exec("DELETE FROM commande WHERE id = $commandeId");
$pdo->exec("DELETE FROM notification WHERE utilisateur_id = 0");

echo PHP_EOL . "===================================================================" . PHP_EOL;
echo " RESULTAT : $succes test(s) réussi(s), $echecs échec(s)" . PHP_EOL;
echo "===================================================================" . PHP_EOL;
exit($echecs > 0 ? 1 : 0);
