<?php

namespace App\Controller;

use App\Entity\Users;
use App\Service\MailerService;
use App\Service\NotificationService;
use App\Service\RecaptchaValidator;
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
        SluggerInterface $slugger,
        MailerService $mailerService,
        RecaptchaValidator $recaptchaValidator,
        NotificationService $notificationService
    ): Response {
        if ($request->isMethod('POST')) {
            // Vérification reCAPTCHA (inscription)
            $token = $request->request->get('g-recaptcha-response');
            if (!$recaptchaValidator->verify($token, $request->getClientIp())) {
                $this->addFlash('error', 'Veuillez confirmer que vous n\'êtes pas un robot.');
                return $this->redirectToRoute('app_register');
            }

            $nom = trim((string) $request->request->get('nom'));
            $prenom = trim((string) $request->request->get('prenom'));
            $dateNaiss = trim((string) $request->request->get('date_naiss'));
            $email = trim((string) $request->request->get('e_mail'));
            $numTel = trim((string) $request->request->get('num_tel'));
            $plainPassword = (string) $request->request->get('mot_de_pass');
            $confirmPassword = (string) $request->request->get('confirm_password');

            // ==================== NOM ====================
            if ($nom === '' || mb_strlen($nom) < 2 || mb_strlen($nom) > 50 || !preg_match('/^[a-zA-ZÀ-ÿ\s-]+$/u', $nom)) {
                $this->addFlash('error', '👤 Nom invalide');
                return $this->redirectToRoute('app_register');
            }

            // ==================== PRENOM ====================
            if ($prenom === '' || mb_strlen($prenom) < 2 || mb_strlen($prenom) > 50 || !preg_match('/^[a-zA-ZÀ-ÿ\s-]+$/u', $prenom)) {
                $this->addFlash('error', '👤 Prénom invalide');
                return $this->redirectToRoute('app_register');
            }

            // ==================== DATE ====================
            $birthDate = \DateTime::createFromFormat('Y-m-d', $dateNaiss);
            if (!$birthDate || $birthDate->format('Y-m-d') !== $dateNaiss) {
                $this->addFlash('error', '📅 Date invalide');
                return $this->redirectToRoute('app_register');
            }

            $today = new \DateTime();
            $age = $today->diff($birthDate)->y;
            if ($birthDate > $today || $age < 18 || $age > 120) {
                $this->addFlash('error', '📅 Date invalide');
                return $this->redirectToRoute('app_register');
            }

            // ==================== EMAIL ====================
            if ($email === '' || mb_strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', '📧 Email invalide');
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
            if ($numTel === '' || mb_strlen($numTel) > 20 || !preg_match('/^(\+216|0)?[2-9][0-9]{7}$/', $numTel)) {
                $this->addFlash('error', '📞 Téléphone invalide');
                return $this->redirectToRoute('app_register');
            }

            // ==================== PASSWORD ====================
            if ($plainPassword === '' || strlen($plainPassword) < 8 || strlen($plainPassword) > 255) {
                $this->addFlash('error', '🔒 Mot de passe invalide (min 8 caractères)');
                return $this->redirectToRoute('app_register');
            }

            if (!preg_match('/[A-Z]/', $plainPassword) || !preg_match('/[a-z]/', $plainPassword) || !preg_match('/[0-9]/', $plainPassword)) {
                $this->addFlash('error', '🔒 Doit contenir majuscule, minuscule et chiffre');
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
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
                $maxSize = 2 * 1024 * 1024;

                if (!in_array($imageFile->getMimeType(), $allowedMimeTypes, true)) {
                    $this->addFlash('error', '❌ Format image invalide');
                    return $this->redirectToRoute('app_register');
                }

                if ($imageFile->getSize() > $maxSize) {
                    $this->addFlash('error', '❌ Image trop lourde (max 2 Mo)');
                    return $this->redirectToRoute('app_register');
                }

                $safeFilename = $slugger->slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME));
                $newFilename = $safeFilename . '-' . uniqid() . '.' . ($imageFile->guessExtension() ?: 'bin');

                try {
                    $imageFile->move($this->getParameter('profile_images_directory'), $newFilename);
                    $user->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', '❌ Erreur upload image');
                    return $this->redirectToRoute('app_register');
                }
            } else {
                $user->setImage('default.png');
            }

            $entityManager->persist($user);
            $entityManager->flush();

            // Notification admin pour nouvelle inscription
            $notificationService->notifyAllAdmins(
                '👤 Nouvel inscrit : ' . $user->getPrenom() . ' ' . $user->getNom() . ' (' . $user->getE_mail() . ')'
            );

            // ==================== GÉNÉRER CODE + ENVOYER EMAIL ====================
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = (new \DateTime())->modify('+15 minutes')->format('Y-m-d H:i:s');

            // Stocker en session (plus simple que la DB pour l'instant)
            $session = $request->getSession();
            $session->set('email_verification_code', $code);
            $session->set('email_verification_user_id', $user->getId());
            $session->set('email_verification_expires', $expiresAt);

            try {
                $mailerService->sendVerificationCode(
                    $user->getE_mail(),
                    $user->getPrenom(),
                    $code
                );
            } catch (\Exception $e) {
                $this->addFlash('error', '❌ Erreur envoi email : ' . $e->getMessage());
                return $this->redirectToRoute('app_register');
            }

            $this->addFlash('success', '✅ Compte créé ! Un code de vérification a été envoyé à ' . $user->getE_mail());
            return $this->redirectToRoute('app_verify_email');
        }

        return $this->render('frontend/register.html.twig');
    }

    #[Route('/verify-email', name: 'app_verify_email', methods: ['GET', 'POST'])]
    public function verifyEmail(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerService $mailerService
    ): Response {
        $session = $request->getSession();
        $userId = $session->get('email_verification_user_id');

        if (!$userId) {
            $this->addFlash('error', '❌ Aucune vérification en cours');
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $code = trim((string) $request->request->get('code'));
            $storedCode = $session->get('email_verification_code');
            $expiresAt = $session->get('email_verification_expires');

            if (!$storedCode || !$expiresAt) {
                $this->addFlash('error', '❌ Code expiré, veuillez recommencer');
                return $this->redirectToRoute('app_register');
            }

            if (new \DateTime() > new \DateTime($expiresAt)) {
                $session->remove('email_verification_code');
                $session->remove('email_verification_user_id');
                $session->remove('email_verification_expires');
                $this->addFlash('error', '⏰ Code expiré, veuillez recommencer');
                return $this->redirectToRoute('app_register');
            }

            if ($code !== $storedCode) {
                $this->addFlash('error', '❌ Code incorrect');
                return $this->redirectToRoute('app_verify_email');
            }

            // Code correct : activer le compte
            $user = $entityManager->getRepository(Users::class)->find($userId);
            if ($user) {
                $user->setEmail_verified(true);
                $entityManager->flush();
            }

            $session->remove('email_verification_code');
            $session->remove('email_verification_user_id');
            $session->remove('email_verification_expires');

            $this->addFlash('success', '✅ Email vérifié avec succès ! Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        $user = $entityManager->getRepository(Users::class)->find($userId);
        return $this->render('frontend/verify_email.html.twig', [
            'email' => $user ? $user->getE_mail() : '',
        ]);
    }

    #[Route('/resend-verification', name: 'app_resend_verification', methods: ['POST'])]
    public function resendVerification(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerService $mailerService
    ): Response {
        $session = $request->getSession();
        $userId = $session->get('email_verification_user_id');

        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        $user = $entityManager->getRepository(Users::class)->find($userId);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = (new \DateTime())->modify('+15 minutes')->format('Y-m-d H:i:s');

        $session->set('email_verification_code', $code);
        $session->set('email_verification_expires', $expiresAt);

        try {
            $mailerService->sendVerificationCode($user->getE_mail(), $user->getPrenom(), $code);
            $this->addFlash('success', '✅ Un nouveau code a été envoyé');
        } catch (\Exception $e) {
            $this->addFlash('error', '❌ Erreur envoi email');
        }

        return $this->redirectToRoute('app_verify_email');
    }

    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerService $mailerService
    ): Response {
        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email'));

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', '📧 Email invalide');
                return $this->redirectToRoute('app_forgot_password');
            }

            $user = $entityManager->getRepository(Users::class)->findOneBy([
                'e_mail' => strtolower($email)
            ]);

            if (!$user) {
                $this->addFlash('success', '✅ Si cet email existe, un code a été envoyé');
                return $this->redirectToRoute('app_forgot_password');
            }

            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = (new \DateTime())->modify('+15 minutes')->format('Y-m-d H:i:s');

            $session = $request->getSession();
            $session->set('reset_password_code', $code);
            $session->set('reset_password_user_id', $user->getId());
            $session->set('reset_password_expires', $expiresAt);

            try {
                $mailerService->sendResetPasswordCode($user->getE_mail(), $user->getPrenom(), $code);
            } catch (\Exception $e) {
                $this->addFlash('error', '❌ Erreur envoi email : ' . $e->getMessage());
                return $this->redirectToRoute('app_forgot_password');
            }

            $this->addFlash('success', '✅ Un code de réinitialisation a été envoyé à ' . $user->getE_mail());
            return $this->redirectToRoute('app_reset_password');
        }

        return $this->render('frontend/forgot_password.html.twig');
    }

    #[Route('/reset-password', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $session = $request->getSession();
        $userId = $session->get('reset_password_user_id');

        if (!$userId) {
            $this->addFlash('error', '❌ Aucune demande de réinitialisation en cours');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $code = trim((string) $request->request->get('code'));
            $newPassword = (string) $request->request->get('new_password');
            $confirmPassword = (string) $request->request->get('confirm_password');

            $storedCode = $session->get('reset_password_code');
            $expiresAt = $session->get('reset_password_expires');

            if (!$storedCode || new \DateTime() > new \DateTime($expiresAt)) {
                $session->remove('reset_password_code');
                $session->remove('reset_password_user_id');
                $session->remove('reset_password_expires');
                $this->addFlash('error', '⏰ Code expiré, veuillez recommencer');
                return $this->redirectToRoute('app_forgot_password');
            }

            if ($code !== $storedCode) {
                $this->addFlash('error', '❌ Code incorrect');
                return $this->redirectToRoute('app_reset_password');
            }

            if (strlen($newPassword) < 8 || !preg_match('/[A-Z]/', $newPassword) || !preg_match('/[a-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
                $this->addFlash('error', '🔒 Mot de passe faible (8+ caractères, majuscule, minuscule, chiffre)');
                return $this->redirectToRoute('app_reset_password');
            }

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', '✓ Les mots de passe ne correspondent pas');
                return $this->redirectToRoute('app_reset_password');
            }

            $user = $entityManager->getRepository(Users::class)->find($userId);
            if ($user) {
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setMot_de_pass($hashedPassword);
                $entityManager->flush();
            }

            $session->remove('reset_password_code');
            $session->remove('reset_password_user_id');
            $session->remove('reset_password_expires');

            $this->addFlash('success', '✅ Mot de passe réinitialisé avec succès !');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('frontend/reset_password.html.twig');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}