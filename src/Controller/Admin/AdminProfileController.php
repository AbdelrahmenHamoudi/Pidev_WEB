<?php

namespace App\Controller\Admin;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/profile')]
class AdminProfileController extends AbstractController
{
    #[Route('', name: 'app_admin_profile', methods: ['GET'])]
    public function profile(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $this->getUser();

        if (!$user instanceof Users) {
            throw $this->createAccessDeniedException('Utilisateur non authentifié.');
        }

        return $this->render('backend/admin/profile.html.twig', [
            'userItem' => $user,
        ]);
    }

    #[Route('/edit', name: 'app_admin_profile_edit', methods: ['GET', 'POST'])]
    public function profileEdit(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $this->getUser();

        if (!$user instanceof Users) {
            throw $this->createAccessDeniedException('Utilisateur non authentifié.');
        }

        if ($request->isMethod('POST')) {
            $nom = trim((string) $request->request->get('nom'));
            $prenom = trim((string) $request->request->get('prenom'));
            $email = trim((string) $request->request->get('e_mail'));
            $numTel = trim((string) $request->request->get('num_tel'));
            $dateNaiss = trim((string) $request->request->get('date_naiss'));

            $currentPassword = (string) $request->request->get('current_password');
            $newPassword = (string) $request->request->get('new_password');
            $confirmPassword = (string) $request->request->get('confirm_password');

            // ==================== NOM ====================
            if ($nom === '') {
                $this->addFlash('error', '👤 Le nom est requis');
                return $this->redirectToRoute('app_admin_profile_edit');
            }

            if (mb_strlen($nom) < 2) {
                $this->addFlash('error', '👤 Minimum 2 caractères pour le nom');
                return $this->redirectToRoute('app_admin_profile_edit');
            }

            if (mb_strlen($nom) > 50) {
                $this->addFlash('error', '👤 Maximum 50 caractères pour le nom');
                return $this->redirectToRoute('app_admin_profile_edit');
            }

            if (!preg_match('/^[a-zA-ZÀ-ÿ\s-]+$/u', $nom)) {
                $this->addFlash('error', '👤 Caractères non autorisés dans le nom');
                return $this->redirectToRoute('app_admin_profile_edit');
            }

            // ==================== PRENOM ====================
            if ($prenom === '') {
                $this->addFlash('error', '👤 Le prénom est requis');
                return $this->redirectToRoute('app_admin_profile_edit');
            }

            if (mb_strlen($prenom) < 2) {
                $this->addFlash('error', '👤 Minimum 2 caractères pour le prénom');
                return $this->redirectToRoute('app_admin_profile_edit');
            }

            if (mb_strlen($prenom) > 50) {
                $this->addFlash('error', '👤 Maximum 50 caractères pour le prénom');
                return $this->redirectToRoute('app_admin_profile_edit');
            }

            if (!preg_match('/^[a-zA-ZÀ-ÿ\s-]+$/u', $prenom)) {
                $this->addFlash('error', '👤 Caractères non autorisés dans le prénom');
                return $this->redirectToRoute('app_admin_profile_edit');
            }

            // ==================== EMAIL ====================
            if ($email === '') {
                $this->addFlash('error', '📧 L\'email est requis');
                return $this->redirectToRoute('app_admin_profile_edit');
            }

            if (mb_strlen($email) > 100) {
                $this->addFlash('error', '📧 Email trop long');
                return $this->redirectToRoute('app_admin_profile_edit');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', '📧 Format email invalide');
                return $this->redirectToRoute('app_admin_profile_edit');
            }

            $existingUser = $entityManager->getRepository(Users::class)->findOneBy([
                'e_mail' => strtolower($email)
            ]);

            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                $this->addFlash('error', '📧 Cet email est déjà utilisé');
                return $this->redirectToRoute('app_admin_profile_edit');
            }

            // ==================== TELEPHONE ====================
            if ($numTel === '') {
                $this->addFlash('error', '📞 Le téléphone est requis');
                return $this->redirectToRoute('app_admin_profile_edit');
            }

            if (mb_strlen($numTel) > 20) {
                $this->addFlash('error', '📞 Numéro trop long');
                return $this->redirectToRoute('app_admin_profile_edit');
            }

            if (!preg_match('/^(\+216|0)?[2-9][0-9]{7}$/', $numTel)) {
                $this->addFlash('error', '📞 Format tunisien invalide');
                return $this->redirectToRoute('app_admin_profile_edit');
            }

            // ==================== DATE NAISSANCE ====================
            if ($dateNaiss === '') {
                $this->addFlash('error', '📅 La date de naissance est requise');
                return $this->redirectToRoute('app_admin_profile_edit');
            }

            $birthDate = \DateTime::createFromFormat('Y-m-d', $dateNaiss);

            if (!$birthDate || $birthDate->format('Y-m-d') !== $dateNaiss) {
                $this->addFlash('error', '📅 Date invalide');
                return $this->redirectToRoute('app_admin_profile_edit');
            }

            $today = new \DateTime();
            $age = $today->diff($birthDate)->y;

            if ($birthDate > $today) {
                $this->addFlash('error', '📅 Date invalide');
                return $this->redirectToRoute('app_admin_profile_edit');
            }

            if ($age < 18) {
                $this->addFlash('error', '📅 Vous devez avoir au moins 18 ans');
                return $this->redirectToRoute('app_admin_profile_edit');
            }

            if ($age > 120) {
                $this->addFlash('error', '📅 Date invalide');
                return $this->redirectToRoute('app_admin_profile_edit');
            }

            // ==================== MOT DE PASSE ====================
            if ($currentPassword !== '' || $newPassword !== '' || $confirmPassword !== '') {
                if ($currentPassword === '') {
                    $this->addFlash('error', '🔒 Le mot de passe actuel est requis');
                    return $this->redirectToRoute('app_admin_profile_edit');
                }

                if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $this->addFlash('error', '🔒 Le mot de passe actuel est incorrect');
                    return $this->redirectToRoute('app_admin_profile_edit');
                }

                if ($newPassword === '') {
                    $this->addFlash('error', '🔒 Le mot de passe est requis');
                    return $this->redirectToRoute('app_admin_profile_edit');
                }

                if (strlen($newPassword) < 8) {
                    $this->addFlash('error', '🔒 Minimum 8 caractères');
                    return $this->redirectToRoute('app_admin_profile_edit');
                }

                if (strlen($newPassword) > 255) {
                    $this->addFlash('error', '🔒 Mot de passe trop long');
                    return $this->redirectToRoute('app_admin_profile_edit');
                }

                if (!preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
                    $this->addFlash('error', '🔒 Doit contenir lettres ET chiffres');
                    return $this->redirectToRoute('app_admin_profile_edit');
                }

                if (!preg_match('/[A-Z]/', $newPassword)) {
                    $this->addFlash('error', '🔒 Doit contenir une majuscule');
                    return $this->redirectToRoute('app_admin_profile_edit');
                }

                if (!preg_match('/[a-z]/', $newPassword)) {
                    $this->addFlash('error', '🔒 Doit contenir une minuscule');
                    return $this->redirectToRoute('app_admin_profile_edit');
                }

                if ($confirmPassword !== $newPassword) {
                    $this->addFlash('error', '✓ Les mots de passe ne correspondent pas');
                    return $this->redirectToRoute('app_admin_profile_edit');
                }

                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setMot_de_pass($hashedPassword);
            }

            // ==================== IMAGE ====================
            $imageFile = $request->files->get('image');

            if ($imageFile) {
                $allowedMimeTypes = [
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                    'image/gif',
                    'image/bmp'
                ];

                $maxSize = 2 * 1024 * 1024;

                if (!in_array($imageFile->getMimeType(), $allowedMimeTypes, true)) {
                    $this->addFlash('error', '❌ Format image invalide');
                    return $this->redirectToRoute('app_admin_profile_edit');
                }

                if ($imageFile->getSize() > $maxSize) {
                    $this->addFlash('error', '❌ Taille max 2 MB');
                    return $this->redirectToRoute('app_admin_profile_edit');
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
                    $this->addFlash('error', '❌ Erreur lors de l’upload de l’image');
                    return $this->redirectToRoute('app_admin_profile_edit');
                }
            }

            // ==================== SAVE ====================
            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setE_mail(strtolower($email));
            $user->setNum_tel($numTel);
            $user->setDate_naiss($dateNaiss);

            $entityManager->flush();

            $this->addFlash('success', '✅ Profil mis à jour avec succès');
            return $this->redirectToRoute('app_admin_profile');
        }

        return $this->render('backend/admin/profile_edit.html.twig', [
            'userItem' => $user,
        ]);
    }
}