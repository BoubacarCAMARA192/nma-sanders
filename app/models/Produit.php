<?php
/**
 * Modele PRODUIT : catalogue de la farine et disponibilite (F18).
 */

declare(strict_types=1);

namespace Model;

use Core\Database;

final class Produit
{
    /** Catalogue visible par le boulanger : uniquement les produits actifs. */
    public static function catalogue(string $recherche = ''): array
    {
        $sql    = 'SELECT * FROM produit WHERE actif = 1';
        $params = [];

        if ($recherche !== '') {
            $sql .= ' AND (libelle LIKE :q OR reference LIKE :q OR description LIKE :q)';
            $params['q'] = '%' . $recherche . '%';
        }

        $sql .= ' ORDER BY libelle';

        return Database::fetchAll($sql, $params);
    }

    public static function tous(): array
    {
        return Database::fetchAll('SELECT * FROM produit ORDER BY actif DESC, libelle');
    }

    public static function trouver(int $id): ?array
    {
        return Database::fetch('SELECT * FROM produit WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    /** Verifie qu'un produit existe, est actif et propose un stock suffisant. */
    public static function disponible(int $id, int $quantite): bool
    {
        $produit = self::trouver($id);

        return $produit !== null
            && (int) $produit['actif'] === 1
            && (int) $produit['stock_disponible'] >= $quantite;
    }

    public static function creer(array $d): int
    {
        return Database::insert(
            'INSERT INTO produit (reference, libelle, description, unite, prix_unitaire, stock_disponible, seuil_alerte, actif)
             VALUES (:reference, :libelle, :description, :unite, :prix, :stock, :seuil, :actif)',
            [
                'reference'   => $d['reference'],
                'libelle'     => $d['libelle'],
                'description' => $d['description'] ?? null,
                'unite'       => $d['unite'] ?? 'sac de 50 kg',
                'prix'        => $d['prix_unitaire'] !== null && $d['prix_unitaire'] !== '' ? (float) $d['prix_unitaire'] : null,
                'stock'       => (int) ($d['stock_disponible'] ?? 0),
                'seuil'       => (int) ($d['seuil_alerte'] ?? 20),
                'actif'       => (int) ($d['actif'] ?? 1),
            ]
        );
    }

    public static function mettreAJour(int $id, array $d): void
    {
        Database::run(
            'UPDATE produit SET reference = :reference, libelle = :libelle, description = :description,
                    unite = :unite, prix_unitaire = :prix, seuil_alerte = :seuil
             WHERE id = :id',
            [
                'reference'   => $d['reference'],
                'libelle'     => $d['libelle'],
                'description' => $d['description'] ?? null,
                'unite'       => $d['unite'] ?? 'sac de 50 kg',
                'prix'        => $d['prix_unitaire'] !== null && $d['prix_unitaire'] !== '' ? (float) $d['prix_unitaire'] : null,
                'seuil'       => (int) ($d['seuil_alerte'] ?? 20),
                'id'          => $id,
            ]
        );
    }

    /** Ajuste le stock (valeurs positives ou negatives). */
    public static function ajusterStock(int $id, int $delta): int
    {
        Database::run(
            'UPDATE produit SET stock_disponible = GREATEST(0, stock_disponible + :delta) WHERE id = :id',
            ['delta' => $delta, 'id' => $id]
        );

        return (int) Database::scalar('SELECT stock_disponible FROM produit WHERE id = :id', ['id' => $id]);
    }

    /** Reserve le stock lors de la validation d'une commande. */
    public static function decrementer(int $id, int $quantite): void
    {
        Database::run(
            'UPDATE produit SET stock_disponible = GREATEST(0, stock_disponible - :q) WHERE id = :id',
            ['q' => $quantite, 'id' => $id]
        );
    }

    public static function restaurer(int $id, int $quantite): void
    {
        Database::run('UPDATE produit SET stock_disponible = stock_disponible + :q WHERE id = :id', ['q' => $quantite, 'id' => $id]);
    }

    public static function basculerActivation(int $id): int
    {
        Database::run('UPDATE produit SET actif = 1 - actif WHERE id = :id', ['id' => $id]);

        return (int) Database::scalar('SELECT actif FROM produit WHERE id = :id', ['id' => $id]);
    }

    public static function enAlerte(): array
    {
        return Database::fetchAll('SELECT * FROM produit WHERE stock_disponible <= seuil_alerte ORDER BY stock_disponible');
    }

    public static function statistiques(): array
    {
        return [
            'total'     => (int) Database::scalar('SELECT COUNT(*) FROM produit'),
            'actifs'    => (int) Database::scalar('SELECT COUNT(*) FROM produit WHERE actif = 1'),
            'alerte'    => (int) Database::scalar('SELECT COUNT(*) FROM produit WHERE stock_disponible <= seuil_alerte'),
            'stock'     => (int) Database::scalar('SELECT COALESCE(SUM(stock_disponible), 0) FROM produit'),
        ];
    }
}
