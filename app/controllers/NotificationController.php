<?php
/**
 * Notifications in-app : consultation et marquage comme lues (F09, F14).
 */

declare(strict_types=1);

namespace Controller;

use Core\Auth;
use Core\Controller;
use Core\Database;
use Core\Notifier;

final class NotificationController extends Controller
{
    public function __construct()
    {
        $this->proteger('boulanger', 'gestionnaire', 'administrateur');
        $this->titre = 'Mes notifications';
    }

    public function index(): void
    {
        $this->vue('notifications', [
            'titre'         => 'Mes notifications',
            'notifications' => Database::fetchAll(
                'SELECT * FROM notification WHERE utilisateur_id = :uid ORDER BY cree_le DESC LIMIT 100',
                ['uid' => (int) Auth::id()]
            ),
        ]);
    }

    public function toutLire(): void
    {
        Notifier::marquerToutesLues((int) Auth::id());
        $this->succes('Toutes les notifications sont marquées comme lues.', 'notifications');
    }

    /** Ouvre une notification : la marque lue puis redirige vers la cible. */
    public function ouvrir(): void
    {
        $id       = (int) (get_param('id') ?? 0);
        $notif    = Database::fetch(
            'SELECT * FROM notification WHERE id = :id AND utilisateur_id = :uid LIMIT 1',
            ['id' => $id, 'uid' => (int) Auth::id()]
        );

        if ($notif === null) {
            $this->rediriger('notifications');
        }

        Database::run('UPDATE notification SET lu = 1 WHERE id = :id', ['id' => $id]);

        if (!empty($notif['lien'])) {
            header('Location: ' . $notif['lien']);
            exit;
        }

        $this->rediriger('notifications');
    }
}
