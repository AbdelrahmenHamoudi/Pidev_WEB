<?php

namespace App\Controller;

use App\Entity\User_2fa;
use App\Entity\Users;
use App\Service\TwoFactorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TwoFactorController extends AbstractController
{
    // =============================================
    // PAGE DE VERIFICATION 2FA (apres login)
    // =============================================
    #[Route('/2fa/verify', name: 'app_2fa_verify', methods: ['GET', 'POST'])]
    public function verify(
        Request $request,
        TwoFactorService $twoFactorService,
        EntityManagerInterface $em
    ): Response {
        $session = $request->getSession();
        $userId = $session->get('2fa_user_id');

        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        $user = $em->getRepository(Users::class)->find($userId);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $user2fa = $twoFactorService->getUser2fa($user);
        if (!$user2fa) {
            $session->remove('2fa_verified');
            $session->remove('2fa_user_id');
            return $this->redirectToRoute('app_login');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $code = trim((string) $request->request->get('code'));
            $isBackup = $request->request->get('use_backup') === '1';

            $valid = false;

            if ($isBackup) {
                $valid = $twoFactorService->verifyBackupCode($user2fa, $code);
                if ($valid) {
                    $em->flush();
                }
            } else {
                $valid = $twoFactorService->verifyCode($user2fa->getSecret_key(), $code);
            }

            if ($valid) {
                $session->set('2fa_verified', true);
                $targetPath = $session->get('2fa_target_path', '/');
                $session->remove('2fa_target_path');

                return $this->redirect($targetPath);
            }

            $error = $isBackup
                ? 'Code de secours invalide.'
                : 'Code invalide. Verifiez votre application Google Authenticator.';
        }

        return $this->render('frontend/2fa_verify.html.twig', [
            'error' => $error,
        ]);
    }

    // =============================================
    // SETUP 2FA (depuis le profil frontend)
    // =============================================
    #[Route('/account/2fa/setup', name: 'app_2fa_setup', methods: ['GET', 'POST'])]
    public function setup(
        Request $request,
        TwoFactorService $twoFactorService,
        EntityManagerInterface $em
    ): Response {
        /** @var Users $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($twoFactorService->is2faEnabled($user)) {
            $this->addFlash('error', 'La 2FA est deja activee.');
            return $this->redirectToRoute('app_user_profile');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $code = trim((string) $request->request->get('code'));
            $secret = $request->request->get('secret');

            $user2fa = $em->getRepository(User_2fa::class)->findOneBy([
                'user_id' => $user,
                'secret_key' => $secret,
                'enabled' => false,
            ]);

            if ($user2fa && $twoFactorService->verifyCode($secret, $code)) {
                $backupCodes = $twoFactorService->activate2fa($user2fa);

                return $this->render('frontend/2fa_backup_codes.html.twig', [
                    'backupCodes' => $backupCodes,
                ]);
            }

            $error = 'Code invalide. Verifiez le code dans Google Authenticator et reessayez.';

            if ($user2fa) {
                $qrCodeDataUri = $twoFactorService->getQrCodeDataUri($user, $secret);

                return $this->render('frontend/2fa_setup.html.twig', [
                    'qrCodeDataUri' => $qrCodeDataUri,
                    'secret' => $secret,
                    'error' => $error,
                ]);
            }
        }

        $user2fa = $twoFactorService->setup2fa($user);
        $secret = $user2fa->getSecret_key();
        $qrCodeDataUri = $twoFactorService->getQrCodeDataUri($user, $secret);

        return $this->render('frontend/2fa_setup.html.twig', [
            'qrCodeDataUri' => $qrCodeDataUri,
            'secret' => $secret,
            'error' => $error,
        ]);
    }

    // =============================================
    // DESACTIVER 2FA
    // =============================================
    #[Route('/account/2fa/disable', name: 'app_2fa_disable', methods: ['POST'])]
    public function disable(
        Request $request,
        TwoFactorService $twoFactorService,
        EntityManagerInterface $em
    ): Response {
        /** @var Users $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Supprimer TOUTES les entrees 2FA de cet utilisateur
        $entries = $em->getRepository(User_2fa::class)->findBy([
            'user_id' => $user,
        ]);

        foreach ($entries as $entry) {
            $em->remove($entry);
        }

        $em->flush();

        // Nettoyer la session 2FA
        $session = $request->getSession();
        $session->remove('2fa_verified');
        $session->remove('2fa_user_id');
        $session->remove('2fa_target_path');

        $this->addFlash('success', '2FA desactivee avec succes.');

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return $this->redirectToRoute('app_admin_profile');
        }

        return $this->redirectToRoute('app_user_profile');
    }

    // =============================================
    // SETUP 2FA ADMIN
    // =============================================
    #[Route('/admin/2fa/setup', name: 'app_admin_2fa_setup', methods: ['GET', 'POST'])]
    public function adminSetup(
        Request $request,
        TwoFactorService $twoFactorService,
        EntityManagerInterface $em
    ): Response {
        /** @var Users $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($twoFactorService->is2faEnabled($user)) {
            $this->addFlash('error', 'La 2FA est deja activee.');
            return $this->redirectToRoute('app_admin_profile');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $code = trim((string) $request->request->get('code'));
            $secret = $request->request->get('secret');

            $user2fa = $em->getRepository(User_2fa::class)->findOneBy([
                'user_id' => $user,
                'secret_key' => $secret,
                'enabled' => false,
            ]);

            if ($user2fa && $twoFactorService->verifyCode($secret, $code)) {
                $backupCodes = $twoFactorService->activate2fa($user2fa);

                return $this->render('backend/2fa_backup_codes.html.twig', [
                    'backupCodes' => $backupCodes,
                ]);
            }

            $error = 'Code invalide. Verifiez le code dans Google Authenticator et reessayez.';

            if ($user2fa) {
                $qrCodeDataUri = $twoFactorService->getQrCodeDataUri($user, $secret);

                return $this->render('backend/2fa_setup.html.twig', [
                    'qrCodeDataUri' => $qrCodeDataUri,
                    'secret' => $secret,
                    'error' => $error,
                ]);
            }
        }

        $user2fa = $twoFactorService->setup2fa($user);
        $secret = $user2fa->getSecret_key();
        $qrCodeDataUri = $twoFactorService->getQrCodeDataUri($user, $secret);

        return $this->render('backend/2fa_setup.html.twig', [
            'qrCodeDataUri' => $qrCodeDataUri,
            'secret' => $secret,
            'error' => $error,
        ]);
    }
}