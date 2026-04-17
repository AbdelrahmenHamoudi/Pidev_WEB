<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NotificationTestController extends AbstractController
{
    #[Route('/admin/test-notif', name: 'app_admin_test_notif', methods: ['POST'])]
    public function testNotif(\Symfony\Component\Mercure\HubInterface $hub): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $update = new \Symfony\Component\Mercure\Update(
            'https://re7la.com/trajets',
            json_encode([
                'action' => 'add_trajet',
                'message' => '🚀 Test de notification serveur réussi !'
            ])
        );

        try {
            $hub->publish($update);
            return new \Symfony\Component\HttpFoundation\JsonResponse(['status' => 'success', 'message' => 'Notification envoyée au hub !']);
        } catch (\Exception $e) {
            return new \Symfony\Component\HttpFoundation\JsonResponse(['status' => 'error', 'message' => 'Erreur de connexion au hub : ' . $e->getMessage()], 500);
        }
    }

}
