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

#[Route('/voitures')]
final class VoitureController extends AbstractController
{
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

    #[Route('/ajouter', name: 'app_voiture_create', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        SluggerInterface $slugger
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
        SluggerInterface $slugger
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
    public function delete(Request $request, Voiture $voiture, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $voiture->getId(), $request->request->get('_csrf_token'))) {
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
        $voiture->setAvecChauffeur(null !== $request->request->get('avec_chauffeur'));
        $voiture->setDisponibilite(null !== $request->request->get('disponibilite'));
        $voiture->setDescription($request->request->get('description'));
        $voiture->setNbPlaces((int) $request->request->get('nb_places'));
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
                $voiture->setImage($urlImage);
            }
            // Sinon on garde l'image existante (ne rien faire)
        }
    }

    #[Route('/api/fetch-image', name: 'api_voiture_fetch_image', methods: ['GET'])]
    public function fetchImage(Request $request): JsonResponse
    {
        $query = $request->query->get('query');
        if (!$query) {
            return new JsonResponse(['error' => 'No query provided'], 400);
        }

        // We use LoremFlickr which provides relevant car images
        $seed = rand(1, 1000);
        $encodedQuery = urlencode($query);
        $imageUrl = "https://loremflickr.com/800/600/car," . $encodedQuery . "?lock=" . $seed;

        return new JsonResponse(['url' => $imageUrl]);
    }
}
