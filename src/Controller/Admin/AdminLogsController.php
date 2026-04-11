<?php

namespace App\Controller\Admin;

use App\Entity\Admin_action_logs;
use App\Entity\Admin_notifications;
use App\Entity\Connexion_logs;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminLogsController extends AbstractController
{
    #[Route('/action-logs', name: 'app_admin_action_logs')]
    public function actionLogs(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $logs = $em->getRepository(Admin_action_logs::class)
            ->createQueryBuilder('l')
            ->orderBy('l.created_at', 'DESC')
            ->setMaxResults(200)
            ->getQuery()
            ->getResult();

        return $this->render('backend/logs/action_logs.html.twig', [
            'logs' => $logs,
        ]);
    }

    #[Route('/connexion-logs', name: 'app_admin_connexion_logs')]
    public function connexionLogs(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $logs = $em->getRepository(Connexion_logs::class)
            ->createQueryBuilder('l')
            ->orderBy('l.login_time', 'DESC')
            ->setMaxResults(200)
            ->getQuery()
            ->getResult();

        return $this->render('backend/logs/connexion_logs.html.twig', [
            'logs' => $logs,
        ]);
    }

    #[Route('/notifications', name: 'app_admin_notifications_index')]
    public function notifications(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $notifs = $em->getRepository(Admin_notifications::class)
            ->createQueryBuilder('n')
            ->orderBy('n.created_at', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('backend/logs/notifications.html.twig', [
            'notifs' => $notifs,
        ]);
    }

    #[Route('/notifications/{id}/read', name: 'app_admin_notifications_read')]
    public function markRead(int $id, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $notif = $em->getRepository(Admin_notifications::class)->find($id);
        if ($notif) {
            $notif->setIs_read(true);
            $em->flush();
        }

        return $this->redirectToRoute('app_admin_notifications_index');
    }

    #[Route('/notifications/mark-all-read', name: 'app_admin_notifications_mark_all_read')]
    public function markAllRead(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $em->createQuery(
            "UPDATE App\Entity\Admin_notifications n SET n.is_read = true WHERE n.is_read = false"
        )->execute();

        return $this->redirectToRoute('app_admin_notifications_index');
    }
}