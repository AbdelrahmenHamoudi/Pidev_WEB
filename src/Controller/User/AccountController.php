<?php

namespace App\Controller\User;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/account')]
#[IsGranted('ROLE_USER')]
class AccountController extends AbstractController
{
    #[Route('/profile', name: 'app_user_profile', methods: ['GET'])]
    public function profile(): Response
    {
        $user = $this->getUser();

        if (!$user instanceof Users) {
            throw $this->createAccessDeniedException('Utilisateur non authentifié.');
        }

        return $this->render('frontend/account/profile.html.twig', [
            'userItem' => $user,
        ]);
    }

    #[Route('/profile/edit', name: 'app_user_profile_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
    ): Response {
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

            if ($nom === '' || mb_strlen($nom) < 2 || mb_strlen($nom) > 50 || !preg_match('/^[a-zA-ZÀ-ÿ\s-]+$/u', $nom)) {
                $this->addFlash('error', 'Nom invalide.');
                return $this->redirectToRoute('app_user_profile_edit');
            }

            if ($prenom === '' || mb_strlen($prenom) < 2 || mb_strlen($prenom) > 50 || !preg_match('/^[a-zA-ZÀ-ÿ\s-]+$/u', $prenom)) {
                $this->addFlash('error', 'Prénom invalide.');
                return $this->redirectToRoute('app_user_profile_edit');
            }

            if ($email === '' || mb_strlen($email) > 100 || str_contains($email, ' ') || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'Email invalide.');
                return $this->redirectToRoute('app_user_profile_edit');
            }

            $existingUser = $entityManager->getRepository(Users::class)->findOneBy([
                'e_mail' => strtolower($email)
            ]);

            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                $this->addFlash('error', 'Cet email est déjà utilisé.');
                return $this->redirectToRoute('app_user_profile_edit');
            }

            if ($numTel === '' || mb_strlen($numTel) > 20 || !preg_match('/^(\+216|0)?[2-9][0-9]{7}$/', $numTel)) {
                $this->addFlash('error', 'Téléphone invalide.');
                return $this->redirectToRoute('app_user_profile_edit');
            }

            if ($dateNaiss === '') {
                $this->addFlash('error', 'Date de naissance requise.');
                return $this->redirectToRoute('app_user_profile_edit');
            }

            $birthDate = \DateTime::createFromFormat('Y-m-d', $dateNaiss);
            if (!$birthDate || $birthDate->format('Y-m-d') !== $dateNaiss) {
                $this->addFlash('error', 'Date de naissance invalide.');
                return $this->redirectToRoute('app_user_profile_edit');
            }

            $today = new \DateTime();
            $age = $today->diff($birthDate)->y;

            if ($birthDate > $today || $age < 18 || $age > 120) {
                $this->addFlash('error', 'Date de naissance invalide.');
                return $this->redirectToRoute('app_user_profile_edit');
            }

            if ($currentPassword !== '' || $newPassword !== '' || $confirmPassword !== '') {
                if ($currentPassword === '' || !$passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $this->addFlash('error', 'Mot de passe actuel incorrect.');
                    return $this->redirectToRoute('app_user_profile_edit');
                }

                if (
                    $newPassword === '' ||
                    strlen($newPassword) < 8 ||
                    strlen($newPassword) > 255 ||
                    !preg_match('/[A-Za-z]/', $newPassword) ||
                    !preg_match('/[0-9]/', $newPassword) ||
                    !preg_match('/[A-Z]/', $newPassword) ||
                    !preg_match('/[a-z]/', $newPassword)
                ) {
                    $this->addFlash('error', 'Nouveau mot de passe invalide.');
                    return $this->redirectToRoute('app_user_profile_edit');
                }

                if ($confirmPassword !== $newPassword) {
                    $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                    return $this->redirectToRoute('app_user_profile_edit');
                }

                $user->setMot_de_pass($passwordHasher->hashPassword($user, $newPassword));
            }

            $imageFile = $request->files->get('image');

            if ($imageFile) {
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
                $maxSize = 2 * 1024 * 1024;

                if (!in_array($imageFile->getMimeType(), $allowedMimeTypes, true)) {
                    $this->addFlash('error', 'Format image invalide.');
                    return $this->redirectToRoute('app_user_profile_edit');
                }

                if ($imageFile->getSize() > $maxSize) {
                    $this->addFlash('error', 'L’image ne doit pas dépasser 2 Mo.');
                    return $this->redirectToRoute('app_user_profile_edit');
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
                    $this->addFlash('error', 'Erreur lors de l’upload de l’image.');
                    return $this->redirectToRoute('app_user_profile_edit');
                }
            }

            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setE_mail(strtolower($email));
            $user->setNum_tel($numTel);
            $user->setDate_naiss($dateNaiss);

            $entityManager->flush();

            $this->addFlash('success', 'Compte modifié avec succès.');
            return $this->redirectToRoute('app_user_profile');
        }

        return $this->render('frontend/account/edit.html.twig', [
            'userItem' => $user,
        ]);
    }

    #[Route('/delete', name: 'app_user_account_delete', methods: ['POST'])]
    public function delete(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user instanceof Users) {
            throw $this->createAccessDeniedException('Utilisateur non authentifié.');
        }

        if (!$this->isCsrfTokenValid('delete_account', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_user_profile');
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $request->getSession()->invalidate();

        return $this->redirectToRoute('app_home');
    }
}