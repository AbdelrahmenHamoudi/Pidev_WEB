<?php

namespace App\Controller\Admin;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
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
            'users' => $users,
            'search' => $search,
            'role' => $role,
            'status' => $status,
            'totalUsers' => $this->countUsers($entityManager),
            'totalAdmins' => $this->countByRole($entityManager, 'admin'),
            'totalSimpleUsers' => $this->countByRole($entityManager, 'user'),
            'totalBanned' => $this->countByStatus($entityManager, 'Banned'),
        ]);
    }

    #[Route('/ajax/list', name: 'app_admin_users_ajax_list', methods: ['GET'])]
    public function ajaxList(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        [$users, $search, $role, $status] = $this->getFilteredUsers($request, $entityManager);

        return $this->render('backend/users/_table.html.twig', [
            'users' => $users,
            'search' => $search,
            'role' => $role,
            'status' => $status,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_users_show', methods: ['GET'])]
    public function show(Users $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('backend/users/show.html.twig', [
            'userItem' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_users_edit', methods: ['GET', 'POST'])]
    public function edit(
        Users $user,
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($request->isMethod('POST')) {
            $nom = trim((string) $request->request->get('nom'));
            $prenom = trim((string) $request->request->get('prenom'));
            $email = trim((string) $request->request->get('e_mail'));
            $numTel = trim((string) $request->request->get('num_tel'));
            $dateNaiss = trim((string) $request->request->get('date_naiss'));
            $role = trim((string) $request->request->get('role'));
            $status = trim((string) $request->request->get('status'));

            // ==================== NOM ====================
            if ($nom === '') {
                $this->addFlash('error', '👤 Le nom est requis');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            if (mb_strlen($nom) < 2) {
                $this->addFlash('error', '👤 Minimum 2 caractères pour le nom');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            if (mb_strlen($nom) > 50) {
                $this->addFlash('error', '👤 Maximum 50 caractères pour le nom');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            if (!preg_match('/^[a-zA-ZÀ-ÿ\s-]+$/u', $nom)) {
                $this->addFlash('error', '👤 Caractères non autorisés dans le nom');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            // ==================== PRENOM ====================
            if ($prenom === '') {
                $this->addFlash('error', '👤 Le prénom est requis');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            if (mb_strlen($prenom) < 2) {
                $this->addFlash('error', '👤 Minimum 2 caractères pour le prénom');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            if (mb_strlen($prenom) > 50) {
                $this->addFlash('error', '👤 Maximum 50 caractères pour le prénom');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            if (!preg_match('/^[a-zA-ZÀ-ÿ\s-]+$/u', $prenom)) {
                $this->addFlash('error', '👤 Caractères non autorisés dans le prénom');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            // ==================== EMAIL ====================
            if ($email === '') {
                $this->addFlash('error', '📧 L\'email est requis');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            if (mb_strlen($email) > 100) {
                $this->addFlash('error', '📧 Email trop long');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            if (str_contains($email, ' ')) {
                $this->addFlash('error', '📧 L\'email ne doit pas contenir d\'espaces');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', '📧 Adresse email invalide');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            $existingUser = $entityManager->getRepository(Users::class)->findOneBy([
                'e_mail' => strtolower($email)
            ]);

            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                $this->addFlash('error', '📧 Cet email est déjà utilisé');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            // ==================== TELEPHONE ====================
            if ($numTel === '') {
                $this->addFlash('error', '📞 Le téléphone est requis');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            if (mb_strlen($numTel) > 20) {
                $this->addFlash('error', '📞 Numéro trop long');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            if (!preg_match('/^(\+216|0)?[2-9][0-9]{7}$/', $numTel)) {
                $this->addFlash('error', '📞 Format tunisien invalide');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            // ==================== DATE NAISSANCE ====================
            if ($dateNaiss === '') {
                $this->addFlash('error', '📅 La date de naissance est requise');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            $birthDate = \DateTime::createFromFormat('Y-m-d', $dateNaiss);

            if (!$birthDate || $birthDate->format('Y-m-d') !== $dateNaiss) {
                $this->addFlash('error', '📅 Date invalide');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            $today = new \DateTime();
            $age = $today->diff($birthDate)->y;

            if ($birthDate > $today) {
                $this->addFlash('error', '📅 Date invalide');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            if ($age < 18) {
                $this->addFlash('error', '📅 L’utilisateur doit avoir au moins 18 ans');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            if ($age > 120) {
                $this->addFlash('error', '📅 Date invalide');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            // ==================== ROLE ====================
            if (!in_array($role, ['admin', 'user'], true)) {
                $this->addFlash('error', '⚠ Rôle invalide');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            // ==================== STATUS ====================
            if (!in_array($status, ['Banned', 'Unbanned'], true)) {
                $this->addFlash('error', '⚠ Statut invalide');
                return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
            }

            // ==================== IMAGE ====================
            $imageFile = $request->files->get('image');

            if ($imageFile) {
                $allowedMimeTypes = [
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                    'image/jpg'
                ];

                $maxSize = 2 * 1024 * 1024;

                if (!in_array($imageFile->getMimeType(), $allowedMimeTypes, true)) {
                    $this->addFlash('error', '❌ Format image invalide. Utilisez JPG, PNG ou WEBP.');
                    return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
                }

                if ($imageFile->getSize() > $maxSize) {
                    $this->addFlash('error', '❌ L’image ne doit pas dépasser 2 Mo.');
                    return $this->redirectToRoute('app_admin_users_edit', ['id' => $user->getId()]);
                }

                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $extension = $imageFile->guessExtension() ?: 'bin';
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

                try {
                    $imageFile->move(
                        $this->getParameter('profile_images_directory'),
                        $newFilename
                    );
                    $user->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', '❌ Erreur lors de l’upload de l’image.');
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

            $this->addFlash('success', '✅ Utilisateur modifié avec succès.');
            return $this->redirectToRoute('app_admin_users_index');
        }

        return $this->render('backend/users/edit.html.twig', [
            'userItem' => $user,
        ]);
    }

    #[Route('/{id}/ban-toggle', name: 'app_admin_users_ban_toggle', methods: ['POST'])]
    public function banToggle(Users $user, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $currentUser = $this->getUser();

        if ($currentUser instanceof Users && $currentUser->getId() === $user->getId()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous ne pouvez pas modifier votre propre statut ici.',
            ], 400);
        }

        $newStatus = $user->getStatus() === 'Banned' ? 'Unbanned' : 'Banned';
        $user->setStatus($newStatus);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'status' => $newStatus,
            'message' => $newStatus === 'Banned'
                ? 'Utilisateur banni avec succès.'
                : 'Utilisateur débanni avec succès.',
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_users_delete', methods: ['POST'])]
    public function delete(Users $user, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $currentUser = $this->getUser();

        if ($currentUser instanceof Users && $currentUser->getId() === $user->getId()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous ne pouvez pas supprimer votre propre compte.',
            ], 400);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Utilisateur supprimé avec succès.',
        ]);
    }

    private function getFilteredUsers(Request $request, EntityManagerInterface $entityManager): array
    {
        $search = trim((string) $request->query->get('search', ''));
        $role = trim((string) $request->query->get('role', ''));
        $status = trim((string) $request->query->get('status', ''));

        $qb = $entityManager->getRepository(Users::class)->createQueryBuilder('u');

        if ($search !== '') {
            $qb->andWhere('u.nom LIKE :search OR u.prenom LIKE :search OR u.e_mail LIKE :search OR u.num_tel LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($role !== '') {
            $qb->andWhere('u.role = :role')
               ->setParameter('role', $role);
        }

        if ($status !== '') {
            $qb->andWhere('u.status = :status')
               ->setParameter('status', $status);
        }

        $qb->orderBy('u.id', 'DESC');

        return [$qb->getQuery()->getResult(), $search, $role, $status];
    }

    private function countUsers(EntityManagerInterface $entityManager): int
    {
        return (int) $entityManager->getRepository(Users::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function countByRole(EntityManagerInterface $entityManager, string $role): int
    {
        return (int) $entityManager->getRepository(Users::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.role = :role')
            ->setParameter('role', $role)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function countByStatus(EntityManagerInterface $entityManager, string $status): int
    {
        return (int) $entityManager->getRepository(Users::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }
}