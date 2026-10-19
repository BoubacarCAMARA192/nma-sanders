<?php
/**
 * Couche presentation : rendu des vues PHP dans un gabarit (layout).
 */

declare(strict_types=1);

namespace Core;

final class View
{
    /** Rend une vue dans le layout donne (par defaut : espace connecte). */
    public static function rendre(string $vue, array $donnees = [], ?string $layout = 'app'): void
    {
        $contenu = self::capturer($vue, $donnees);

        if ($layout === null) {
            echo $contenu;

            return;
        }

        $donnees['contenu'] = $contenu;
        echo self::capturer('layouts/' . $layout, $donnees);
    }

    /** Capture le rendu d'un fichier de vue et le retourne sous forme de chaine. */
    public static function capturer(string $vue, array $donnees = []): string
    {
        $fichier = VIEW_PATH . '/' . str_replace('.', '/', $vue) . '.php';
        if (!is_file($fichier)) {
            throw new \RuntimeException('Vue introuvable : ' . $vue);
        }

        extract($donnees, EXTR_SKIP);
        ob_start();
        require $fichier;

        return (string) ob_get_clean();
    }

    /** Affichage d'une page d'erreur complete (gabarit adapte a la connexion). */
    public static function erreur(int $code, string $titre, string $message): void
    {
        http_response_code($code);

        $donnees = ['code' => $code, 'titre' => $titre, 'message' => $message, 'routeCourante' => '', 'messages' => []];

        if (Auth::connecte()) {
            $utilisateur = Auth::utilisateur();
            $donnees += [
                'utilisateur'   => $utilisateur,
                'titre'         => $titre,
                'notifications' => Notifier::dernieres((int) Auth::id(), 5),
                'notifNonLues'  => Notifier::nonLues((int) Auth::id()),
            ];
            self::rendre('errors/erreur', $donnees, 'app');

            return;
        }

        self::rendre('errors/erreur', $donnees, 'public');
    }
}
