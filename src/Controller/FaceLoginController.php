<?php

namespace App\Controller;

use App\Entity\Users;
use App\Service\ConnexionLogger;
use App\Service\FaceLoginService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class FaceLoginController extends AbstractController
{
    private FaceLoginService $faceService;
    private EntityManagerInterface $em;
    private ConnexionLogger $connexionLogger;
    private TokenStorageInterface $tokenStorage;
    private RequestStack $requestStack;

    public function __construct(
        FaceLoginService $faceService,
        EntityManagerInterface $em,
        ConnexionLogger $connexionLogger,
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack
    ) {
        $this->faceService = $faceService;
        $this->em = $em;
        $this->connexionLogger = $connexionLogger;
        $this->tokenStorage = $tokenStorage;
        $this->requestStack = $requestStack;
    }

    // =============================================
    // PAGE DE LOGIN FACIAL
    // =============================================
    #[Route('/face-login', name: 'app_face_login', methods: ['GET'])]
    public function loginPage(): Response
    {
        $serverOnline = $this->faceService->isServerRunning();

        return $this->render('frontend/face_login.html.twig', [
            'serverOnline' => $serverOnline,
        ]);
    }

    // =============================================
    // API : TENTATIVE DE LOGIN FACIAL (AJAX)
    // =============================================
    #[Route('/face-login/attempt', name: 'app_face_login_attempt', methods: ['POST'])]
    public function loginAttempt(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $imageBase64 = $data['image'] ?? null;

        if (!$imageBase64) {
            return $this->json(['success' => false, 'message' => 'Aucune image fournie'], 400);
        }

        // Nettoyer le prefix data:image/jpeg;base64,
        if (str_contains($imageBase64, ',')) {
            $imageBase64 = explode(',', $imageBase64)[1];
        }

        // Appeler Flask
        $result = $this->faceService->loginWithFace($imageBase64);

        if (!($result['success'] ?? false)) {
            $this->connexionLogger->logFailure(null, 'Face ID: ' . ($result['message'] ?? 'Visage non reconnu'));
            return $this->json([
                'success' => false,
                'message' => $result['message'] ?? 'Visage non reconnu',
            ]);
        }

        // Trouver l'utilisateur par email (username dans Flask = email)
        $email = $result['username'] ?? null;
        $confidence = $result['confidence'] ?? 0;

        if (!$email) {
            return $this->json(['success' => false, 'message' => 'Utilisateur non identifie'], 400);
        }

        $user = $this->em->getRepository(Users::class)->findOneBy([
            'e_mail' => strtolower(trim($email)),
        ]);

        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Utilisateur non trouve dans la base'], 404);
        }

        // Verifier si le compte est banni
        if ($user->getStatus() && strtolower($user->getStatus()) === 'banned') {
            $this->connexionLogger->logFailure($user, 'Face ID: Compte banni');
            return $this->json(['success' => false, 'message' => 'Votre compte a ete suspendu'], 403);
        }

        // Connecter l'utilisateur programmatiquement
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);

        // Sauvegarder le token dans la session
        $session = $this->requestStack->getCurrentRequest()->getSession();
        $session->set('_security_main', serialize($token));

        // Logger la connexion reussie
        $this->connexionLogger->logSuccess($user);

        // Determiner la redirection
        $redirectUrl = '/';
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            $redirectUrl = '/admin';
        }

        return $this->json([
            'success' => true,
            'message' => 'Connexion reussie',
            'confidence' => $confidence,
            'user' => $user->getPrenom() . ' ' . $user->getNom(),
            'redirectUrl' => $redirectUrl,
        ]);
    }

    // =============================================
    // PAGE D'ENREGISTREMENT FACIAL (depuis profil)
    // =============================================
    #[Route('/account/face-id/setup', name: 'app_face_id_setup', methods: ['GET'])]
    public function setupPage(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $serverOnline = $this->faceService->isServerRunning();

        return $this->render('frontend/face_register.html.twig', [
            'serverOnline' => $serverOnline,
        ]);
    }

    // =============================================
    // API : ENREGISTRER SON VISAGE (AJAX)
    // =============================================
    #[Route('/account/face-id/register', name: 'app_face_id_register', methods: ['POST'])]
    public function registerFace(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non connecte'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $imageBase64 = $data['image'] ?? null;

        if (!$imageBase64) {
            return $this->json(['success' => false, 'message' => 'Aucune image fournie'], 400);
        }

        if (str_contains($imageBase64, ',')) {
            $imageBase64 = explode(',', $imageBase64)[1];
        }

        $result = $this->faceService->registerFace($user->getE_mail(), $imageBase64);

        return $this->json($result);
    }

    // =============================================
    // API : DESACTIVER FACE ID (AJAX)
    // =============================================
    #[Route('/account/face-id/disable', name: 'app_face_id_disable', methods: ['POST'])]
    public function disableFace(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non connecte'], 401);
        }

        $result = $this->faceService->disableFace($user->getE_mail());

        return $this->json($result);
    }

    // =============================================
    // API : VERIFIER STATUT FACE ID (AJAX)
    // =============================================
    #[Route('/account/face-id/check', name: 'app_face_id_check', methods: ['GET'])]
    public function checkFaceStatus(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['enabled' => false]);
        }

        $enabled = $this->faceService->checkFace($user->getE_mail());

        return $this->json(['enabled' => $enabled]);
    }

    // =============================================
    // API : HEALTH CHECK (AJAX)
    // =============================================
    #[Route('/face-api/health', name: 'app_face_api_health', methods: ['GET'])]
    public function healthCheck(): JsonResponse
    {
        $online = $this->faceService->isServerRunning();

        return $this->json([
            'online' => $online,
        ]);
    }
}