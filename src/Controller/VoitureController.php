<?php

namespace App\Controller;

use App\Entity\Voiture;
use App\Repository\VoitureRepository;
use App\Repository\TrajetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

use App\Service\PdfService;

#[Route('/voitures')]
final class VoitureController extends AbstractController
{
    private $pdfService;

    public function __construct(PdfService $pdfService) {
        $this->pdfService = $pdfService;
    }
    #[Route('/dashboard', name: 'app_admin_voiture_dashboard')]
    public function adminDashboard(): Response
    {
        return $this->render('backend/admin/dashboard.html.twig');
    }

    #[Route('/login', name: 'app_admin_login')]
    public function login(): Response
    {
        return $this->render('backend/admin/login.html.twig');
    }

    #[Route('/profile', name: 'app_admin_profile')]
    public function profile(): Response
    {
        return $this->render('backend/admin/profile.html.twig');
    }

    #[Route('/profile/edit', name: 'app_admin_profile_edit')]
    public function profileEdit(Request $request): Response
    {
        // Pour l'instant, on retourne simplement le template
        // Plus tard, on pourra ajouter la logique de modification
        return $this->render('backend/admin/profile_edit.html.twig');
    }
    //crud
    #[Route('', name: 'app_voiture', methods: ['GET'])]
    public function index(Request $request, VoitureRepository $voitureRepo, TrajetRepository $trajetRepo): Response
    {
        $search = $request->query->get('search', '');
        $marque = $request->query->get('marque', '');
        $sortBy = $request->query->get('sort', 'id');
        $order  = $request->query->get('order', 'ASC');

        $qb = $voitureRepo->createQueryBuilder('v');

        if ($search) {
            $qb->andWhere('v.marque LIKE :search OR v.modele LIKE :search OR v.immatriculation LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        if ($marque) {
            $qb->andWhere('v.marque = :marque')
               ->setParameter('marque', $marque);
        }

        $sortableFields = ['id', 'marque', 'modele', 'prixKM', 'nbPlaces'];
        if (!in_array($sortBy, $sortableFields)) { $sortBy = 'id'; }
        if (!in_array(strtoupper($order), ['ASC', 'DESC'])) { $order = 'ASC'; }
        
        $doctrineSortBy = match ($sortBy) {
            'id' => 'id_voiture',
            'prixKM' => 'prix_km',
            'nbPlaces' => 'nb_places',
            default => $sortBy,
        };
        $qb->orderBy('v.' . $doctrineSortBy, $order);

        $voitures = $qb->getQuery()->getResult();

        $marques = $voitureRepo->createQueryBuilder('v')
            ->select('DISTINCT v.marque')
            ->orderBy('v.marque', 'ASC')
            ->getQuery()->getResult();

        return $this->render('voiture/index.html.twig', [
            'voitures' => $voitures,
            'trajets'  => $trajetRepo->findAll(),
            'search'   => $search,
            'marque'   => $marque,
            'marques'  => array_column($marques, 'marque'),
            'sortBy'   => $sortBy,
            'order'    => $order,
        ]);
    }

    #[Route('/export/pdf', name: 'app_voiture_export_pdf', methods: ['GET'])]
    public function exportPdf(VoitureRepository $voitureRepo): Response
    {
        $voitures = $voitureRepo->findAll();
        $html = $this->renderView('backend/pdf/voitures.html.twig', [
            'voitures' => $voitures
        ]);

        return $this->pdfService->showPdfFile($html, "Catalogue_Voitures_" . date('d_m_Y'));
    }

    #[Route('/ajouter', name: 'app_voiture_create', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        SluggerInterface $slugger,
        HubInterface $hub
    ): Response {
        $voiture = new Voiture();

        if ($request->isMethod('POST')) {
            $this->hydrateVoiture($voiture, $request);
            $this->handleImageUpload($voiture, $request, $slugger);

            $errors = $validator->validate($voiture);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            } else {
                $em->persist($voiture);
                $em->flush();

                $update = new Update(
                    'https://re7la.com/voitures',
                    json_encode([
                        'action' => 'add',
                        'message' => "Nouvelle voiture ajoutée : {$voiture->getMarque()} {$voiture->getModele()}",
                        'voiture' => ['id' => $voiture->getId(), 'marque' => $voiture->getMarque(), 'modele' => $voiture->getModele()]
                    ])
                );
                
                try {
                    $hub->publish($update);
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'La voiture a été ajoutée. (Mode Dev: Notification temps réel désactivée, hub introuvable)');
                }

                $this->addFlash('success', 'Voiture ajoutée avec succès !');
                return $this->redirectToRoute('app_voiture');
            }
        }

        return $this->render('voiture/newvoiture.html.twig', [
            'voiture' => $voiture,
            'title'   => 'Ajouter une voiture',
        ]);
    }

    #[Route('/{id}', name: 'app_voiture_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Voiture $voiture): Response
    {
        return $this->render('voiture/listvoiture.html.twig', ['voiture' => $voiture]);
    }

    #[Route('/{id}/modifier', name: 'app_voiture_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Voiture $voiture,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        SluggerInterface $slugger,
        HubInterface $hub
    ): Response {
        if ($request->isMethod('POST')) {
            $this->hydrateVoiture($voiture, $request);
            $this->handleImageUpload($voiture, $request, $slugger);

            $errors = $validator->validate($voiture);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            } else {
                $em->flush();

                $update = new Update(
                    'https://re7la.com/voitures',
                    json_encode([
                        'action' => 'edit',
                        'message' => "La voiture {$voiture->getMarque()} {$voiture->getModele()} a été mise à jour.",
                        'voiture' => ['id' => $voiture->getId(), 'marque' => $voiture->getMarque(), 'modele' => $voiture->getModele()]
                    ])
                );
                try {
                    $hub->publish($update);
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Mise à jour réussie. (Notifications temps réel indisponibles)');
                }

                $this->addFlash('success', 'Voiture modifiée avec succès !');
                return $this->redirectToRoute('app_voiture_show', ['id' => $voiture->getId()]);
            }
        }

        return $this->render('voiture/newvoiture.html.twig', [
            'voiture' => $voiture,
            'title'   => 'Modifier la voiture',
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_voiture_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Voiture $voiture, EntityManagerInterface $em, HubInterface $hub): Response
    {
        if ($this->isCsrfTokenValid('delete' . $voiture->getId(), $request->request->get('_csrf_token'))) {
            $marque = $voiture->getMarque();
            $modele = $voiture->getModele();
            $idVoiture = $voiture->getId();
            // Supprimer le fichier image si c'est un fichier local
            $image = $voiture->getImage();
            if ($image && str_starts_with($image, '/uploads/voitures/')) {
                $filePath = $this->getParameter('kernel.project_dir') . '/public' . $image;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $em->remove($voiture);
            $em->flush();

            $update = new Update(
                'https://re7la.com/voitures',
                json_encode([
                    'action' => 'delete',
                    'message' => "La voiture $marque $modele a été retirée de la flotte.",
                    'voiture' => ['id' => $idVoiture, 'marque' => $marque, 'modele' => $modele]
                ])
            );
            try {
                $hub->publish($update);
            } catch (\Exception $e) {
            }

            $this->addFlash('success', 'Voiture supprimée avec succès !');
        }

        return $this->redirectToRoute('app_voiture');
    }

    // ── Méthodes privées ──────────────────────────────────────────

    private function hydrateVoiture(Voiture $voiture, Request $request): void
    {
        $voiture->setMarque($request->request->get('marque'));
        $voiture->setModele($request->request->get('modele'));
        $voiture->setImmatriculation($request->request->get('immatriculation'));
        $voiture->setPrixKM((float) $request->request->get('prix_KM'));
        $voiture->setAvecChauffeur($request->request->get('avec_chauffeur') === '1');
        $voiture->setDisponibilite($request->request->get('disponibilite') === '1');
        $voiture->setDescription($request->request->get('description'));
        $voiture->setNbPlaces((int) $request->request->get('nb_places'));
        
        // AI Specs fields
        $voiture->setPuissance((int) $request->request->get('puissance'));
        $voiture->setVitesseMax((int) $request->request->get('vitesse_max'));
        $voiture->setAcceleration((float) $request->request->get('acceleration'));
        $voiture->setConsommation((float) $request->request->get('consommation'));
        $voiture->setBoiteVitesse($request->request->get('boite_vitesse'));
    }

    private function handleImageUpload(Voiture $voiture, Request $request, SluggerInterface $slugger): void
    {
        $uploadedFile = $request->files->get('image_file');

        if ($uploadedFile) {
            // Supprimer l'ancienne image locale si elle existe
            $oldImage = $voiture->getImage();
            if ($oldImage && str_starts_with($oldImage, '/uploads/voitures/')) {
                $oldPath = $this->getParameter('kernel.project_dir') . '/public' . $oldImage;
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            $originalName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeName     = $slugger->slug($originalName);
            $newName      = $safeName . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/voitures';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $uploadedFile->move($uploadDir, $newName);
            $voiture->setImage('/uploads/voitures/' . $newName);

        } else {
            // Pas de fichier uploadé → garder l'URL saisie manuellement (si fournie)
            $urlImage = $request->request->get('image_url');
            if ($urlImage) {
                // Si l'utilisateur génère dynamiquement via l'IA Pollinations
                if (substr($urlImage, 0, 31) === 'https://image.pollinations.ai') {
                    $context = stream_context_create([
                        "ssl" => ["verify_peer" => false, "verify_peer_name" => false],
                        "http" => ["timeout" => 60, "header" => "User-Agent: Mozilla/5.0\r\n"]
                    ]);
                    $content = @file_get_contents($urlImage, false, $context);
                    if ($content !== false) {
                        $safeName = $slugger->slug($voiture->getMarque() . '-' . $voiture->getModele());
                        $newName  = $safeName . '-' . uniqid() . '.jpg';
                        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/voitures';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        file_put_contents($uploadDir . '/' . $newName, $content);
                        $voiture->setImage('/uploads/voitures/' . $newName);
                    } else {
                        $voiture->setImage($urlImage);
                    }
                } else {
                    $voiture->setImage($urlImage);
                }
            }
            // Sinon on garde l'image existante (ne rien faire)
        }
    }

    #[Route('/api/fetch-image', name: 'api_voiture_fetch_image', methods: ['GET'])]
    public function fetchImage(Request $request, SluggerInterface $slugger): JsonResponse
    {
        set_time_limit(120);
        ignore_user_abort(true);
        $query = $request->query->get('query');
        if (!$query) {
            return new JsonResponse(['error' => 'No query provided'], 400);
        }

        // Utilisation de Pollinations AI pour une véritable génération d'image par IA
        $seed = rand(1, 10000);
        $prompt = "A professional photography of a " . $query . " car, studio lighting, hyperrealistic, 4k, high resolution";
        $externalUrl = "https://image.pollinations.ai/prompt/" . urlencode($prompt) . "?width=800&height=600&nologo=true&seed=" . $seed;

        try {
            // Téléchargement de l'image via cURL (plus robuste que file_get_contents sous Windows)
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $externalUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
            
            $content = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($content === false || $httpCode !== 200) {
                return new JsonResponse(['error' => 'Impossible de générer l\'image (' . $httpCode . '): ' . $error], 500);
            }

            $safeName = $slugger->slug($query);
            $fileName = $safeName . '-' . uniqid() . '.jpg';

            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/voitures';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            file_put_contents($uploadDir . '/' . $fileName, $content);
            $localUrl = '/uploads/voitures/' . $fileName;

            return new JsonResponse(['url' => $localUrl]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la génération : ' . $e->getMessage()], 500);
        }
    }
}
