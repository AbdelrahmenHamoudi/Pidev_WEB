<?php

namespace App\Controller\Admin;

use App\Entity\Users;
use App\Service\AdminActionLogger;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/users')]
class AdminUserController extends AbstractController
{
    #[Route('', name: 'app_admin_users_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        [$users, $search, $role, $status] = $this->getFilteredUsers($request, $entityManager);

        return $this->render('backend/users/index.html.twig', [
            'users'            => $users,
            'search'           => $search,
            'role'             => $role,
            'status'           => $status,
            'totalUsers'       => $this->countUsers($entityManager),
            'totalAdmins'      => $this->countByRole($entityManager, 'admin'),
            'totalSimpleUsers' => $this->countByRole($entityManager, 'user'),
            'totalBanned'      => $this->countByStatus($entityManager, 'Banned'),
        ]);
    }

    #[Route('/ajax/list', name: 'app_admin_users_ajax_list', methods: ['GET'])]
    public function ajaxList(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        [$users, $search, $role, $status] = $this->getFilteredUsers($request, $entityManager);

        return $this->render('backend/users/_table.html.twig', [
            'users'  => $users,
            'search' => $search,
            'role'   => $role,
            'status' => $status,
        ]);
    }

    #[Route('/export/pdf', name: 'app_admin_users_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Réutilise les filtres actifs (search, role, status)
        [$users, $search, $role, $status] = $this->getFilteredUsers($request, $entityManager);

        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        $html = $this->generateUsersPdfHtml($users, $search, $role, $status);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="liste_utilisateurs_' . date('Y-m-d_His') . '.pdf"',
            ]
        );
    }

    #[Route('/{id}', name: 'app_admin_users_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Users $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('backend/users/show.html.twig', [
            'userItem' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_users_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        Users $user,
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        AdminActionLogger $actionLogger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($request->isMethod('POST')) {
            $nom       = trim((string) $request->request->get('nom'));
            $prenom    = trim((string) $request->request->get('prenom'));
            $email     = trim((string) $request->request->get('e_mail'));
            $numTel    = trim((string) $request->request->get('num_tel'));
            $dateNaiss = trim((string) $request->request->get('date_naiss'));
            $role      = trim((string) $request->request->get('role'));
            $status    = trim((string) $request->request->get('status'));

            if ($nom === '' || mb_strlen($nom) < 2 || mb_strlen($nom) > 50 || !preg_match('/^[a-zA-ZÀ-ÿ\s-]+$/u', $nom)) {
                $this->addFlash('error', '👤 Nom invalide');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            if ($prenom === '' || mb_strlen($prenom) < 2 || mb_strlen($prenom) > 50 || !preg_match('/^[a-zA-ZÀ-ÿ\s-]+$/u', $prenom)) {
                $this->addFlash('error', '👤 Prénom invalide');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            if ($email === '' || mb_strlen($email) > 100 || str_contains($email, ' ') || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', '📧 Email invalide');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            $existingUser = $entityManager->getRepository(Users::class)->findOneBy(['e_mail' => strtolower($email)]);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                $this->addFlash('error', '📧 Cet email est déjà utilisé');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            if ($numTel === '' || mb_strlen($numTel) > 20 || !preg_match('/^(\+216|0)?[2-9][0-9]{7}$/', $numTel)) {
                $this->addFlash('error', '📞 Téléphone invalide');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            $birthDate = \DateTime::createFromFormat('Y-m-d', $dateNaiss);
            if (!$birthDate || $birthDate->format('Y-m-d') !== $dateNaiss) {
                $this->addFlash('error', '📅 Date invalide');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            $today = new \DateTime();
            $age   = $today->diff($birthDate)->y;
            if ($birthDate > $today || $age < 18 || $age > 120) {
                $this->addFlash('error', '📅 Date invalide');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            if (!in_array($role, ['admin', 'user'], true)) {
                $this->addFlash('error', '⚠ Rôle invalide');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            if (!in_array($status, ['Banned', 'Unbanned'], true)) {
                $this->addFlash('error', '⚠ Statut invalide');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
                $maxSize          = 2 * 1024 * 1024;

                if (!in_array($imageFile->getMimeType(), $allowedMimeTypes, true)) {
                    $this->addFlash('error', '❌ Format image invalide.');
                    return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
                }

                if ($imageFile->getSize() > $maxSize) {
                    $this->addFlash('error', '❌ Image trop lourde (max 2 Mo).');
                    return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
                }

                $safeFilename = $slugger->slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME));
                $newFilename  = $safeFilename . '-' . uniqid() . '.' . ($imageFile->guessExtension() ?: 'bin');

                try {
                    $imageFile->move($this->getParameter('profile_images_directory'), $newFilename);
                    $user->setImage($newFilename);
                } catch (FileException) {
                    $this->addFlash('error', '❌ Erreur upload image.');
                    return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
                }
            }

            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setE_mail(strtolower($email));
            $user->setNum_tel($numTel);
            $user->setDate_naiss($dateNaiss);
            $user->setRole($role);
            $user->setStatus($status);
            $entityManager->flush();

            // Log de l'action
            $currentUser = $this->getUser();
            if ($currentUser instanceof Users) {
                $actionLogger->log(
                    $currentUser,
                    'EDIT_USER',
                    'user',
                    $user->getId(),
                    $user->getPrenom() . ' ' . $user->getNom() . ' (' . $user->getE_mail() . ')',
                    'Informations modifiées par l\'admin'
                );
            }

            $this->addFlash('success', '✅ Utilisateur modifié avec succès.');
            return $this->redirectToRoute('app_admin_users_index');
        }

        return $this->render('backend/users/edit.html.twig', [
            'userItem' => $user,
        ]);
    }

    #[Route('/{id}/ban-toggle', name: 'app_admin_users_ban_toggle', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function banToggle(
        Users $user,
        EntityManagerInterface $entityManager,
        AdminActionLogger $actionLogger,
        NotificationService $notificationService
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $currentUser = $this->getUser();

        if ($currentUser instanceof Users && $currentUser->getId() === $user->getId()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous ne pouvez pas modifier votre propre statut.',
            ], 400);
        }

        $newStatus = $user->getStatus() === 'Banned' ? 'Unbanned' : 'Banned';
        $user->setStatus($newStatus);
        $entityManager->flush();

        if ($currentUser instanceof Users) {
            $actionLogger->log(
                $currentUser,
                $newStatus === 'Banned' ? 'BAN_USER' : 'UNBAN_USER',
                'user',
                $user->getId(),
                $user->getPrenom() . ' ' . $user->getNom() . ' (' . $user->getE_mail() . ')',
                'Statut changé en : ' . $newStatus
            );

            $notificationService->notifyUser(
                $user,
                $newStatus === 'Banned'
                    ? 'Votre compte a été suspendu par un administrateur.'
                    : 'Votre compte a été réactivé par un administrateur.'
            );
        }

        return new JsonResponse([
            'success' => true,
            'status'  => $newStatus,
            'message' => $newStatus === 'Banned'
                ? 'Utilisateur banni avec succès.'
                : 'Utilisateur débanni avec succès.',
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_users_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        Users $user,
        EntityManagerInterface $entityManager,
        AdminActionLogger $actionLogger
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $currentUser = $this->getUser();

        if ($currentUser instanceof Users && $currentUser->getId() === $user->getId()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous ne pouvez pas supprimer votre propre compte.',
            ], 400);
        }

        // Log AVANT la suppression
        if ($currentUser instanceof Users) {
            $actionLogger->log(
                $currentUser,
                'DELETE_USER',
                'user',
                $user->getId(),
                $user->getPrenom() . ' ' . $user->getNom() . ' (' . $user->getE_mail() . ')',
                'Compte supprimé définitivement'
            );
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Utilisateur supprimé avec succès.',
        ]);
    }

    // ──────────────────────────────────────────────
    // Helpers privés
    // ──────────────────────────────────────────────

    private function getFilteredUsers(Request $request, EntityManagerInterface $entityManager): array
    {
        $search = trim((string) $request->query->get('search', ''));
        $role   = trim((string) $request->query->get('role', ''));
        $status = trim((string) $request->query->get('status', ''));

        $qb = $entityManager->getRepository(Users::class)->createQueryBuilder('u');

        if ($search !== '') {
            $qb->andWhere('u.nom LIKE :search OR u.prenom LIKE :search OR u.e_mail LIKE :search OR u.num_tel LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($role !== '') {
            $qb->andWhere('u.role = :role')->setParameter('role', $role);
        }

        if ($status !== '') {
            $qb->andWhere('u.status = :status')->setParameter('status', $status);
        }

        $qb->orderBy('u.id', 'DESC');

        return [$qb->getQuery()->getResult(), $search, $role, $status];
    }

    private function countUsers(EntityManagerInterface $entityManager): int
    {
        return (int) $entityManager->getRepository(Users::class)
            ->createQueryBuilder('u')->select('COUNT(u.id)')
            ->getQuery()->getSingleScalarResult();
    }

    private function countByRole(EntityManagerInterface $entityManager, string $role): int
    {
        return (int) $entityManager->getRepository(Users::class)
            ->createQueryBuilder('u')->select('COUNT(u.id)')
            ->where('u.role = :role')->setParameter('role', $role)
            ->getQuery()->getSingleScalarResult();
    }

    private function countByStatus(EntityManagerInterface $entityManager, string $status): int
    {
        return (int) $entityManager->getRepository(Users::class)
            ->createQueryBuilder('u')->select('COUNT(u.id)')
            ->where('u.status = :status')->setParameter('status', $status)
            ->getQuery()->getSingleScalarResult();
    }

    private function generateUsersPdfHtml(array $users, string $search, string $role, string $status): string
    {
        $date = date('d/m/Y H:i');
        $total = count($users);

        // Afficher les filtres actifs
        $filtersInfo = [];
        if ($search !== '') {
            $filtersInfo[] = 'Recherche : "' . htmlspecialchars($search) . '"';
        }
        if ($role !== '') {
            $filtersInfo[] = 'Rôle : ' . htmlspecialchars($role);
        }
        if ($status !== '') {
            $filtersInfo[] = 'Statut : ' . htmlspecialchars($status);
        }
        $filtersText = !empty($filtersInfo) ? ' — Filtres : ' . implode(', ', $filtersInfo) : '';

        $html = '
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Helvetica, sans-serif; font-size: 11px; color: #333; }
                h1 { color: #1ABC9C; text-align: center; margin-bottom: 5px; font-size: 22px; }
                .subtitle { text-align: center; color: #7f8c8d; margin-bottom: 20px; font-size: 11px; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th { background-color: #2C3E50; color: white; padding: 10px 8px; text-align: left; font-size: 11px; }
                td { padding: 8px; border-bottom: 1px solid #ecf0f1; }
                tr:nth-child(even) { background-color: #f9f9f9; }
                .badge { padding: 3px 8px; border-radius: 3px; color: white; font-size: 10px; font-weight: bold; display: inline-block; }
                .admin { background-color: #2C3E50; }
                .user { background-color: #1ABC9C; }
                .banned { background-color: #e74c3c; }
                .unbanned { background-color: #27ae60; }
                .footer { margin-top: 20px; text-align: center; color: #95a5a6; font-size: 9px; border-top: 1px solid #ecf0f1; padding-top: 10px; }
            </style>
        </head>
        <body>
            <h1>RE7LA — Liste des Utilisateurs</h1>
            <div class="subtitle">Exporté le ' . $date . ' — Total : ' . $total . ' utilisateur(s)' . $filtersText . '</div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Date naissance</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Email vérifié</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($users as $user) {
            $roleClass = $user->getRole() === 'admin' ? 'admin' : 'user';
            $statusClass = $user->getStatus() === 'Banned' ? 'banned' : 'unbanned';
            $emailVerified = $user->getEmail_verified() ? 'Oui' : 'Non';

            $html .= '
                <tr>
                    <td>' . $user->getId() . '</td>
                    <td>' . htmlspecialchars($user->getNom()) . '</td>
                    <td>' . htmlspecialchars($user->getPrenom()) . '</td>
                    <td>' . htmlspecialchars($user->getE_mail()) . '</td>
                    <td>' . htmlspecialchars($user->getNum_tel()) . '</td>
                    <td>' . htmlspecialchars($user->getDate_naiss()) . '</td>
                    <td><span class="badge ' . $roleClass . '">' . strtoupper($user->getRole()) . '</span></td>
                    <td><span class="badge ' . $statusClass . '">' . $user->getStatus() . '</span></td>
                    <td>' . $emailVerified . '</td>
                </tr>';
        }

        $html .= '
                </tbody>
            </table>
            <div class="footer">RE7LA Admin — Document généré automatiquement</div>
        </body>
        </html>';

        return $html;
    }
}