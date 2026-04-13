<?php

namespace App\Controller\Api;

use App\Entity\Users;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]
class UsersApiController extends AbstractController
{
    // POST /api/register - Inscription (public)
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['nom'], $data['prenom'], $data['e_mail'], $data['mot_de_pass'], $data['num_tel'], $data['date_naiss'])) {
            return new JsonResponse(['error' => 'Champs manquants'], 400);
        }

        $existing = $em->getRepository(Users::class)->findOneBy([
            'e_mail' => strtolower(trim($data['e_mail']))
        ]);
        if ($existing) {
            return new JsonResponse(['error' => 'Email déjà utilisé'], 409);
        }

        $user = new Users();
        $user->setNom($data['nom']);
        $user->setPrenom($data['prenom']);
        $user->setE_mail($data['e_mail']);
        $user->setNum_tel($data['num_tel']);
        $user->setDate_naiss($data['date_naiss']);
        $user->setMot_de_pass($hasher->hashPassword($user, $data['mot_de_pass']));

        $em->persist($user);
        $em->flush();

        return new JsonResponse([
            'message' => 'Utilisateur créé avec succès. Connectez-vous sur /api/login pour obtenir un token.',
            'user' => $this->serializeUser($user)
        ], 201);
    }

    // POST /api/test-email - Endpoint de test pour vérifier l'envoi d'email
    #[Route('/test-email', name: 'test_email', methods: ['POST'])]
    public function testEmail(Request $request, MailerService $mailerService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'])) {
            return new JsonResponse(['error' => 'Email manquant'], 400);
        }

        try {
            $mailerService->sendVerificationCode(
                $data['email'],
                'Utilisateur Test',
                '123456'
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Email envoyé avec succès à ' . $data['email']
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // GET /api/me - Infos du user connecté (protégé par JWT)
    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var Users $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        return new JsonResponse($this->serializeUser($user), 200);
    }

    // GET /api/users - Liste des users (protégé par JWT)
    #[Route('/users', name: 'users_list', methods: ['GET'])]
    public function list(EntityManagerInterface $em): JsonResponse
    {
        $users = $em->getRepository(Users::class)->findAll();
        $data = array_map(fn($u) => $this->serializeUser($u), $users);
        return new JsonResponse($data, 200);
    }

    // GET /api/users/export/pdf - Exporter la liste des users en PDF (protégé par JWT)
    #[Route('/users/export/pdf', name: 'users_export_pdf', methods: ['GET'])]
    public function exportPdf(EntityManagerInterface $em): Response
    {
        $users = $em->getRepository(Users::class)->findAll();

        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        $html = $this->generateUsersHtml($users);

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

    // GET /api/users/{id} - Détails d'un user (protégé par JWT)
    #[Route('/users/{id}', name: 'users_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, EntityManagerInterface $em): JsonResponse
    {
        $user = $em->getRepository(Users::class)->find($id);

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
        }

        return new JsonResponse($this->serializeUser($user), 200);
    }

    private function serializeUser(Users $user): array
    {
        return [
            'id' => $user->getId(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'e_mail' => $user->getE_mail(),
            'num_tel' => $user->getNum_tel(),
            'date_naiss' => $user->getDate_naiss(),
            'image' => $user->getImage(),
            'role' => $user->getRole(),
            'status' => $user->getStatus(),
            'email_verified' => $user->getEmail_verified(),
        ];
    }

    private function generateUsersHtml(array $users): string
    {
        $date = date('d/m/Y H:i');
        $total = count($users);

        $html = '
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Helvetica, sans-serif; font-size: 11px; color: #333; }
                h1 { color: #2c3e50; text-align: center; margin-bottom: 5px; }
                .subtitle { text-align: center; color: #7f8c8d; margin-bottom: 20px; font-size: 12px; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th { background-color: #3498db; color: white; padding: 8px; text-align: left; font-size: 11px; }
                td { padding: 6px 8px; border-bottom: 1px solid #ecf0f1; }
                tr:nth-child(even) { background-color: #f9f9f9; }
                .badge { padding: 3px 8px; border-radius: 3px; color: white; font-size: 10px; font-weight: bold; }
                .admin { background-color: #e74c3c; }
                .user { background-color: #3498db; }
                .banned { background-color: #c0392b; }
                .unbanned { background-color: #27ae60; }
                .footer { margin-top: 20px; text-align: center; color: #95a5a6; font-size: 9px; }
            </style>
        </head>
        <body>
            <h1>Liste des Utilisateurs</h1>
            <div class="subtitle">Exporté le ' . $date . ' — Total : ' . $total . ' utilisateurs</div>
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
            <div class="footer">Pidev Web Integration — Document généré automatiquement</div>
        </body>
        </html>';

        return $html;
    }
}