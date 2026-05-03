<?php

namespace App\Controller\Communaute;

use App\Entity\Commentaire;
use App\Entity\Publication;
use App\Service\CensorshipService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/communaute')]
class FrontPublicationController extends AbstractController
{
    // ================================================================
    // INDEX — feed of publications
    // ================================================================

    #[Route('', name: 'app_communaute_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $typeCible = $request->query->get('typeCible', '');
        $search    = trim((string) $request->query->get('search', ''));

        $qb = $em->getRepository(Publication::class)
            ->createQueryBuilder('p')
            ->leftJoin('p.id_utilisateur', 'u')
            ->leftJoin('p.commentaires', 'c')
            ->addSelect('u', 'c');

        if ($typeCible !== '') {
            $qb->andWhere('p.typeCible = :typeCible')
               ->setParameter('typeCible', $typeCible);
        }

        if ($search !== '') {
            $qb->andWhere('p.DescriptionP LIKE :search OR u.nom LIKE :search OR u.prenom LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $qb->orderBy('p.idPublication', 'DESC');

        $publications = $qb->getQuery()->getResult();

        return $this->render('frontend/communaute/communaute_index.html.twig', [
            'publications' => $publications,
            'typeCible'    => $typeCible,
            'search'       => $search,
            'typeCibles'   => ['HEBERGEMENT', 'ACTIVITE', 'TRANSPORT'],
        ]);
    }

    // ================================================================
    // NEW PUBLICATION
    // ================================================================

    #[Route('/nouvelle-publication', name: 'app_communaute_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, CensorshipService $censorshipService): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($request->isMethod('POST')) {
            $errors = $this->validatePublicationForm($request, true, $censorshipService);

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('app_communaute_new');
            }

            $publication = new Publication();
            $this->hydratePublication($publication, $request, $slugger);
            $publication->setId_utilisateur($this->getUser());

            $now = (new \DateTime())->format('Y-m-d H:i:s');
            $publication->setDateCreation($now);
            $publication->setDateModif($now);
            $publication->setStatutP('VALIDE');
            $publication->setEstVerifie(false);

            $em->persist($publication);
            $em->flush();

            $this->addFlash('success', '✅ Publication soumise ! Elle sera visible après validation.');
            return $this->redirectToRoute('app_communaute_index');
        }

        return $this->render('frontend/communaute/communaute_new.html.twig', [
            'typeCibles' => ['HEBERGEMENT', 'ACTIVITE', 'TRANSPORT'],
        ]);
    }

    // ================================================================
    // SHOW SINGLE PUBLICATION + COMMENTS
    // ================================================================

    #[Route('/{idPublication}', name: 'app_communaute_show', methods: ['GET'])]
    public function show(Publication $publication): Response
    {
        return $this->render('frontend/communaute/communaute_show.html.twig', [
            'publication' => $publication,
        ]);
    }

    // ================================================================
    // EDIT MY PUBLICATION
    // ================================================================

    #[Route('/{idPublication}/modifier', name: 'app_communaute_edit', methods: ['GET', 'POST'])]
    public function edit(Publication $publication, Request $request, EntityManagerInterface $em, SluggerInterface $slugger, CensorshipService $censorshipService): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($publication->getId_utilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette publication.');
        }

        if ($request->isMethod('POST')) {
            $errors = $this->validatePublicationForm($request, false, $censorshipService);

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('app_communaute_edit', ['idPublication' => $publication->getIdPublication()]);
            }

            $this->hydratePublication($publication, $request, $slugger);
            $publication->setDateModif((new \DateTime())->format('Y-m-d H:i:s'));
            $publication->setStatutP('EN_ATTENTE');
            $publication->setEstVerifie(false);

            $em->flush();

            $this->addFlash('success', '✅ Publication modifiée. Elle sera visible après re-validation.');
            return $this->redirectToRoute('app_communaute_index');
        }

        return $this->render('frontend/communaute/communaute_edit.html.twig', [
            'publication' => $publication,
            'typeCibles'  => ['HEBERGEMENT', 'ACTIVITE', 'TRANSPORT'],
        ]);
    }

    // ================================================================
    // DELETE MY PUBLICATION
    // ================================================================

    #[Route('/{idPublication}/supprimer', name: 'app_communaute_delete', methods: ['POST'])]
    public function delete(Publication $publication, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($publication->getId_utilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cette publication.');
        }

        $em->remove($publication);
        $em->flush();

        $this->addFlash('success', '🗑 Publication supprimée.');
        return $this->redirectToRoute('app_communaute_index');
    }

    // ================================================================
    // ADD COMMENT
    // ================================================================

    #[Route('/{idPublication}/commenter', name: 'app_communaute_comment_add', methods: ['POST'])]
    public function addComment(Publication $publication, Request $request, EntityManagerInterface $em, CensorshipService $censorshipService): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $contenu = trim((string) $request->request->get('contenuC', ''));

        // Check for forbidden words
        $censorshipError = $censorshipService->validateText($contenu, 'commentaire');
        if ($censorshipError) {
            $this->addFlash('error', $censorshipError);
            return $this->redirectToRoute('app_communaute_show', ['idPublication' => $publication->getIdPublication()]);
        }

        if ($contenu === '' || mb_strlen($contenu) < 2) {
            $this->addFlash('error', '💬 Le commentaire doit comporter au moins 2 caractères.');
        } elseif (mb_strlen($contenu) > 200) {
            $this->addFlash('error', '💬 Le commentaire ne peut pas dépasser 200 caractères.');
        } else {
            $commentaire = new Commentaire();
            $commentaire->setContenuC($contenu);
            $commentaire->setDateCreationC((new \DateTime())->format('Y-m-d H:i:s'));
            $commentaire->setStatutC(true);
            $commentaire->setIdPublication($publication);
            $commentaire->setId_utilisateur($this->getUser());

            $em->persist($commentaire);
            $em->flush();

            $this->addFlash('success', '💬 Commentaire ajouté !');
        }

        return $this->redirectToRoute('app_communaute_show', ['idPublication' => $publication->getIdPublication()]);
    }

    // ================================================================
    // EDIT MY COMMENT (AJAX-friendly POST)
    // ================================================================

    #[Route('/commentaire/{idCommentaire}/modifier', name: 'app_communaute_comment_edit', methods: ['POST'])]
    public function editComment(Commentaire $commentaire, Request $request, EntityManagerInterface $em, CensorshipService $censorshipService): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($commentaire->getId_utilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce commentaire.');
        }

        $contenu = trim((string) $request->request->get('contenuC', ''));

        // Check for forbidden words
        $censorshipError = $censorshipService->validateText($contenu, 'commentaire');
        if ($censorshipError) {
            $this->addFlash('error', $censorshipError);
            return $this->redirectToRoute('app_communaute_show', [
                'idPublication' => $commentaire->getIdPublication()->getIdPublication(),
            ]);
        }

        if ($contenu !== '' && mb_strlen($contenu) >= 2 && mb_strlen($contenu) <= 200) {
            $commentaire->setContenuC($contenu);
            $em->flush();
            $this->addFlash('success', '✏️ Commentaire modifié.');
        } else {
            $this->addFlash('error', '💬 Contenu invalide (2-200 caractères).');
        }

        return $this->redirectToRoute('app_communaute_show', [
            'idPublication' => $commentaire->getIdPublication()->getIdPublication(),
        ]);
    }

    // ================================================================
    // DELETE MY COMMENT
    // ================================================================

    #[Route('/commentaire/{idCommentaire}/supprimer', name: 'app_communaute_comment_delete', methods: ['POST'])]
    public function deleteComment(Commentaire $commentaire, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($commentaire->getId_utilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce commentaire.');
        }

        $publicationId = $commentaire->getIdPublication()->getIdPublication();

        $em->remove($commentaire);
        $em->flush();

        $this->addFlash('success', '🗑 Commentaire supprimé.');
        return $this->redirectToRoute('app_communaute_show', ['idPublication' => $publicationId]);
    }

    // ================================================================
    // PRIVATE HELPERS
    // ================================================================

    private function validatePublicationForm(Request $request, bool $imageRequired = true, ?CensorshipService $censorshipService = null): array
    {
        $errors      = [];
        $description = trim((string) $request->request->get('DescriptionP', ''));
        $typeCible   = trim((string) $request->request->get('typeCible', ''));

        // Check for forbidden words FIRST
        if ($description !== '' && $censorshipService) {
            $censorshipError = $censorshipService->validateText($description, 'description');
            if ($censorshipError) {
                $errors[] = $censorshipError;
            }
        }

        if ($description === '') {
            $errors[] = '📝 La description est requise.';
        } elseif (mb_strlen($description) < 5) {
            $errors[] = '📝 La description doit comporter au moins 5 caractères.';
        } elseif (mb_strlen($description) > 200) {
            $errors[] = '📝 La description ne peut pas dépasser 200 caractères.';
        }

        if (!in_array($typeCible, ['HEBERGEMENT', 'ACTIVITE', 'TRANSPORT'], true)) {
            $errors[] = '🏷 Type cible invalide.';
        }

        $imageFile = $request->files->get('ImgURL');
        if ($imageRequired && !$imageFile) {
            $errors[] = '🖼 Une image est requise.';
        }

        if ($imageFile) {
            $allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $maxBytes = 2 * 1024 * 1024;

            if (!in_array($imageFile->getMimeType(), $allowed, true)) {
                $errors[] = '🖼 Format image invalide. Utilisez JPG, PNG, WEBP ou GIF.';
            } elseif ($imageFile->getSize() > $maxBytes) {
                $errors[] = '🖼 L\'image ne doit pas dépasser 2 Mo.';
            }
        }

        return $errors;
    }

    private function hydratePublication(Publication $publication, Request $request, SluggerInterface $slugger): void
    {
        $publication->setDescriptionP(trim((string) $request->request->get('DescriptionP', '')));
        $publication->setTypeCible(trim((string) $request->request->get('typeCible', '')));

        $imageFile = $request->files->get('ImgURL');
        if ($imageFile) {
            $safeFilename = $slugger->slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME));
            $extension    = $imageFile->guessExtension() ?: 'bin';
            $newFilename  = $safeFilename . '-' . uniqid() . '.' . $extension;

            try {
                $imageFile->move(
                    $this->getParameter('publication_images_directory'),
                    $newFilename
                );
                $publication->setImgURL($newFilename);
            } catch (FileException) {
                // Keep old image on edit if upload fails
            }
        }
    }
    
    // ================================================================
    // SECTION DES JEUX
    // ================================================================
    #[Route('/jeux', name: 'app_communaute_games', methods: ['GET'])]
    public function games(): Response
    {
        return $this->render('frontend/communaute/_games_fab.html.twig');
    }
}