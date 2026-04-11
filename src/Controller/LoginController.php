<?php

namespace App\Controller;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('frontend/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
    ): Response {
        if ($request->isMethod('POST')) {
            $nom = trim((string) $request->request->get('nom'));
            $prenom = trim((string) $request->request->get('prenom'));
            $dateNaiss = trim((string) $request->request->get('date_naiss'));
            $email = trim((string) $request->request->get('e_mail'));
            $numTel = trim((string) $request->request->get('num_tel'));
            $plainPassword = (string) $request->request->get('mot_de_pass');
            $confirmPassword = (string) $request->request->get('confirm_password');

            // ==================== NOM ====================
            if ($nom === '') {
                $this->addFlash('error', '👤 Le nom est requis');
                return $this->redirectToRoute('app_register');
            }

            if (mb_strlen($nom) < 2) {
                $this->addFlash('error', '👤 Minimum 2 caractères pour le nom');
                return $this->redirectToRoute('app_register');
            }

            if (mb_strlen($nom) > 50) {
                $this->addFlash('error', '👤 Maximum 50 caractères pour le nom');
                return $this->redirectToRoute('app_register');
            }

            if (!preg_match('/^[a-zA-ZÀ-ÿ\s-]+$/u', $nom)) {
                $this->addFlash('error', '👤 Caractères non autorisés dans le nom');
                return $this->redirectToRoute('app_register');
            }

            // ==================== PRENOM ====================
            if ($prenom === '') {
                $this->addFlash('error', '👤 Le prénom est requis');
                return $this->redirectToRoute('app_register');
            }

            if (mb_strlen($prenom) < 2) {
                $this->addFlash('error', '👤 Minimum 2 caractères pour le prénom');
                return $this->redirectToRoute('app_register');
            }

            if (mb_strlen($prenom) > 50) {
                $this->addFlash('error', '👤 Maximum 50 caractères pour le prénom');
                return $this->redirectToRoute('app_register');
            }

            if (!preg_match('/^[a-zA-ZÀ-ÿ\s-]+$/u', $prenom)) {
                $this->addFlash('error', '👤 Caractères non autorisés dans le prénom');
                return $this->redirectToRoute('app_register');
            }

            // ==================== DATE ====================
            if ($dateNaiss === '') {
                $this->addFlash('error', '📅 La date de naissance est requise');
                return $this->redirectToRoute('app_register');
            }

            $birthDate = \DateTime::createFromFormat('Y-m-d', $dateNaiss);

            if (!$birthDate || $birthDate->format('Y-m-d') !== $dateNaiss) {
                $this->addFlash('error', '📅 Date invalide');
                return $this->redirectToRoute('app_register');
            }

            $today = new \DateTime();
            $age = $today->diff($birthDate)->y;

            if ($birthDate > $today) {
                $this->addFlash('error', '📅 Date invalide');
                return $this->redirectToRoute('app_register');
            }

            if ($age < 18) {
                $this->addFlash('error', '📅 Vous devez avoir au moins 18 ans');
                return $this->redirectToRoute('app_register');
            }

            if ($age > 120) {
                $this->addFlash('error', '📅 Date invalide');
                return $this->redirectToRoute('app_register');
            }

            // ==================== EMAIL ====================
            if ($email === '') {
                $this->addFlash('error', '📧 L\'email est requis');
                return $this->redirectToRoute('app_register');
            }

            if (mb_strlen($email) > 100) {
                $this->addFlash('error', '📧 Email trop long');
                return $this->redirectToRoute('app_register');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', '📧 Veuillez saisir une adresse email valide');
                return $this->redirectToRoute('app_register');
            }

            $existingUser = $entityManager->getRepository(Users::class)->findOneBy([
                'e_mail' => strtolower($email)
            ]);

            if ($existingUser) {
                $this->addFlash('error', '📧 Cet email est déjà utilisé');
                return $this->redirectToRoute('app_register');
            }

            // ==================== TELEPHONE ====================
            if ($numTel === '') {
                $this->addFlash('error', '📞 Le téléphone est requis');
                return $this->redirectToRoute('app_register');
            }

            if (mb_strlen($numTel) > 20) {
                $this->addFlash('error', '📞 Numéro trop long');
                return $this->redirectToRoute('app_register');
            }

            if (!preg_match('/^(\+216|0)?[2-9][0-9]{7}$/', $numTel)) {
                $this->addFlash('error', '📞 Format tunisien invalide');
                return $this->redirectToRoute('app_register');
            }

            // ==================== PASSWORD ====================
            if ($plainPassword === '') {
                $this->addFlash('error', '🔒 Le mot de passe est requis');
                return $this->redirectToRoute('app_register');
            }

            if (strlen($plainPassword) < 8) {
                $this->addFlash('error', '🔒 Minimum 8 caractères');
                return $this->redirectToRoute('app_register');
            }

            if (strlen($plainPassword) > 255) {
                $this->addFlash('error', '🔒 Mot de passe trop long');
                return $this->redirectToRoute('app_register');
            }

            if (!preg_match('/[A-Za-z]/', $plainPassword) || !preg_match('/[0-9]/', $plainPassword)) {
                $this->addFlash('error', '🔒 Doit contenir lettres ET chiffres');
                return $this->redirectToRoute('app_register');
            }

            if (!preg_match('/[A-Z]/', $plainPassword)) {
                $this->addFlash('error', '🔒 Doit contenir une majuscule');
                return $this->redirectToRoute('app_register');
            }

            if (!preg_match('/[a-z]/', $plainPassword)) {
                $this->addFlash('error', '🔒 Doit contenir une minuscule');
                return $this->redirectToRoute('app_register');
            }

            if ($confirmPassword !== $plainPassword) {
                $this->addFlash('error', '✓ Les mots de passe ne correspondent pas');
                return $this->redirectToRoute('app_register');
            }

            $user = new Users();
            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setDate_naiss($dateNaiss);
            $user->setE_mail(strtolower($email));
            $user->setNum_tel($numTel);
            $user->setRole('user');
            $user->setStatus('Unbanned');
            $user->setEmail_verified(false);

            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setMot_de_pass($hashedPassword);

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
                    return $this->redirectToRoute('app_register');
                }

                if ($imageFile->getSize() > $maxSize) {
                    $this->addFlash('error', '❌ L’image ne doit pas dépasser 2 Mo.');
                    return $this->redirectToRoute('app_register');
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
                    return $this->redirectToRoute('app_register');
                }
            } else {
                $user->setImage('default.png');
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', '✅ Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('frontend/register.html.twig');
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(): Response
    {
        return $this->render('frontend/forgot_password.html.twig');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}