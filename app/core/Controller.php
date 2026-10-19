<?php
/**
 * Controleur de base : acces securise a la session, au rendu et aux redirections.
 */

declare(strict_types=1);

namespace Core;

abstract class Controller
{
    protected array $utilisateur = [];
    protected string $titre = APP_NAME;

    /** Exige une connexion et l'un des roles fournis. */
    protected function proteger(string ...$roles): void
    {
        $this->utilisateur = Auth::exigerRole(...$roles);
    }

    /** Rend une vue de l'espace connecte. */
    protected function vue(string $vue, array $donnees = []): void
    {
        View::rendre($vue, array_merge($this->donneesCommunes(), $donnees), 'app');
    }

    /** Rend une vue publique (sans menu de navigation) avec les messages flash. */
    protected function vuePublique(string $vue, array $donnees = []): void
    {
        View::rendre($vue, array_merge(['messages' => Flash::recuperer()], $donnees), 'public');
    }

    /** Donnees injectees dans toutes les pages de l'espace connecte. */
    protected function donneesCommunes(): array
    {
        $userId = Auth::id() ?? 0;

        return [
            'utilisateur'   => $this->utilisateur,
            'titre'         => $this->titre,
            'messages'      => Flash::recuperer(),
            'notifications' => Notifier::dernieres($userId, 6),
            'notifNonLues'  => Notifier::nonLues($userId),
            'routeCourante' => (string) (get_param('r') ?? ''),
        ];
    }

    protected function rediriger(string $route, array $params = []): void
    {
        header('Location: ' . url($route, $params));
        exit;
    }

    protected function succes(string $message, ?string $route = null, array $params = []): void
    {
        Flash::succes($message);
        if ($route !== null) {
            $this->rediriger($route, $params);
        }
    }

    protected function erreur(string $message, ?string $route = null, array $params = []): void
    {
        Flash::erreur($message);
        if ($route !== null) {
            $this->rediriger($route, $params);
        }
    }

    /** Identifiant boulanger de l'utilisateur connecte (0 si non applicable). */
    protected function boulangerId(): int
    {
        return (int) ($this->utilisateur['boulanger_id'] ?? 0);
    }

    /** Entier issu de la requete (0 si absent ou invalide). */
    protected function entier(string $cle): int
    {
        $brut = get_param($cle) ?? post($cle);

        return is_numeric($brut) ? (int) $brut : 0;
    }
}
