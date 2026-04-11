<?php

namespace App\Controller\Admin;

use App\Entity\Publication;
use App\Entity\Commentaire;
use App\Entity\Users;
use App\Repository\PublicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/publications')]
class AdminPublicationController extends AbstractController
{
    // ================================================================
    // LIST
    // ================================================================

    #[Route('', name: 'app_admin_publication_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        [$publications, $search, $typeCible, $statutP] = $this->getFilteredPublications($request, $em);

        return $this->render('backendCommunaute/publication/index.html.twig', [
            'publications'   => $publications,
            'search'         => $search,
            'typeCible'      => $typeCible,
            'statutP'        => $statutP,
            'totalPublications' => $this->countPublications($em),
            'totalVerifiees'    => $this->countByVerified($em, true),
            'totalNonVerifiees' => $this->countByVerified($em, false),
        ]);
    }

    #[Route('/ajax/list', name: 'app_admin_publication_ajax_list', methods: ['GET'])]
    public function ajaxList(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        [$publications, $search, $typeCible, $statutP] = $this->getFilteredPublications($request, $em);

        return $this->render('backendCommunaute/publication/_table.html.twig', [
            'publications' => $publications,
            'search'       => $search,
            'typeCible'    => $typeCible,
            'statutP'      => $statutP,
        ]);
    }
    // ================================================================
    // CREATE
    // ================================================================

    #[Route('/new', name: 'app_admin_publication_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($request->isMethod('POST')) {
            $errors = $this->validatePublicationForm($request, $em, null, $slugger);

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('app_admin_publication_new');
            }

            $publication = new Publication();
            $this->hydratePublication($publication, $request, $em, $slugger);

            $now = (new \DateTime())->format('Y-m-d H:i:s');
            $publication->setDateCreation($now);
            $publication->setDateModif($now);

            $em->persist($publication);
            $em->flush();

            $this->addFlash('success', '✅ Publication créée avec succès.');
            return $this->redirectToRoute('app_admin_publication_index');
        }

        return $this->render('backendCommunaute/publication/new.html.twig', [
            'typeCibles' => $this->getTypeCibles(),
            'statuts'    => $this->getStatuts(),
            'users'      => $em->getRepository(Users::class)->findAll(),
        ]);
    }

    // ================================================================
    // SHOW
    // ================================================================

    #[Route('/{idPublication}', name: 'app_admin_publication_show', methods: ['GET'])]
    public function show(Publication $publication): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('backendCommunaute/publication/show.html.twig', [
            'publication' => $publication,
        ]);
    }


    // ================================================================
    // EDIT
    // ================================================================

    #[Route('/{idPublication}/edit', name: 'app_admin_publication_edit', methods: ['GET', 'POST'])]
    public function edit(
        Publication $publication,
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($request->isMethod('POST')) {
            $errors = $this->validatePublicationForm($request, $em, $publication, $slugger);

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('app_admin_publication_edit', [
                    'idPublication' => $publication->getIdPublication(),
                ]);
            }

            $this->hydratePublication($publication, $request, $em, $slugger);
            $publication->setDateModif((new \DateTime())->format('Y-m-d H:i:s'));

            $em->flush();

            $this->addFlash('success', '✅ Publication modifiée avec succès.');
            return $this->redirectToRoute('app_admin_publication_index');
        }

        return $this->render('backendCommunaute/publication/edit.html.twig', [
            'publication' => $publication,
            'typeCibles'  => $this->getTypeCibles(),
            'statuts'     => $this->getStatuts(),
            'users'       => $em->getRepository(Users::class)->findAll(),
        ]);
    }

    // ================================================================
    // DELETE
    // ================================================================

    #[Route('/{idPublication}/delete', name: 'app_admin_publication_delete', methods: ['POST'])]
    public function delete(Publication $publication, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $em->remove($publication);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Publication supprimée avec succès.',
        ]);
    }

    // ================================================================
    // TOGGLE VÉRIFIÉ
    // ================================================================

    #[Route('/{idPublication}/toggle-verifie', name: 'app_admin_publication_toggle_verifie', methods: ['POST'])]
    public function toggleVerifie(Publication $publication, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $publication->setEstVerifie(!$publication->getEstVerifie());
        $em->flush();

        return new JsonResponse([
            'success'   => true,
            'verifie'   => $publication->getEstVerifie(),
            'message'   => $publication->getEstVerifie()
                ? 'Publication vérifiée.'
                : 'Publication marquée non vérifiée.',
        ]);
    }

    // ================================================================
    // COMMENTAIRES — list under a publication
    // ================================================================

    #[Route('/{idPublication}/commentaires', name: 'app_admin_publication_commentaires', methods: ['GET'])]
    public function commentaires(Publication $publication): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('backendCommunaute/commentaire/index.html.twig', [
            'publication'  => $publication,
            'commentaires' => $publication->getCommentaires(),
        ]);
    }

    // ================================================================
    // PRIVATE HELPERS
    // ================================================================

    private function validatePublicationForm(
        Request $request,
        EntityManagerInterface $em,
        ?Publication $existing,
        SluggerInterface $slugger
    ): array {
        $errors = [];

        $description = trim((string) $request->request->get('DescriptionP', ''));
        $typeCible   = trim((string) $request->request->get('typeCible', ''));
        $statutP     = trim((string) $request->request->get('statutP', ''));
        $userId      = (int) $request->request->get('id_utilisateur', 0);

        if ($description === '') {
            $errors[] = '📝 La description est requise.';
        } elseif (mb_strlen($description) < 5) {
            $errors[] = '📝 La description doit comporter au moins 5 caractères.';
        } elseif (mb_strlen($description) > 200) {
            $errors[] = '📝 La description ne peut pas dépasser 200 caractères.';
        }

        if (!in_array($typeCible, $this->getTypeCibles(), true)) {
            $errors[] = '🏷 Type cible invalide.';
        }

        if (!in_array($statutP, $this->getStatuts(), true)) {
            $errors[] = '📌 Statut invalide.';
        }

        if ($userId === 0) {
            $errors[] = '👤 Veuillez sélectionner un utilisateur.';
        } elseif ($em->getRepository(Users::class)->find($userId) === null) {
            $errors[] = '👤 Utilisateur introuvable.';
        }

        $imageFile = $request->files->get('ImgURL');
        if ($existing === null && !$imageFile) {
            $errors[] = '🖼 Une image est requise pour créer une publication.';
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

    private function hydratePublication(
        Publication $publication,
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): void {
        $userId = (int) $request->request->get('id_utilisateur', 0);
        $user   = $em->getRepository(Users::class)->find($userId);

        $publication->setId_utilisateur($user);
        $publication->setDescriptionP(trim((string) $request->request->get('DescriptionP', '')));
        $publication->setTypeCible(trim((string) $request->request->get('typeCible', '')));
        $publication->setStatutP(trim((string) $request->request->get('statutP', '')));
        $publication->setEstVerifie((bool) $request->request->get('estVerifie', false));

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

    private function getFilteredPublications(Request $request, EntityManagerInterface $em): array
    {
        $search    = trim((string) $request->query->get('search', ''));
        $typeCible = trim((string) $request->query->get('typeCible', ''));
        $statutP   = trim((string) $request->query->get('statutP', ''));

        $qb = $em->getRepository(Publication::class)
            ->createQueryBuilder('p')
            ->leftJoin('p.id_utilisateur', 'u')
            ->addSelect('u');

        if ($search !== '') {
            $qb->andWhere('p.DescriptionP LIKE :search OR u.nom LIKE :search OR u.prenom LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($typeCible !== '') {
            $qb->andWhere('p.typeCible = :typeCible')
               ->setParameter('typeCible', $typeCible);
        }

        if ($statutP !== '') {
            $qb->andWhere('p.statutP = :statutP')
               ->setParameter('statutP', $statutP);
        }

        $qb->orderBy('p.idPublication', 'DESC');

        return [$qb->getQuery()->getResult(), $search, $typeCible, $statutP];
    }

    private function countPublications(EntityManagerInterface $em): int
    {
        return (int) $em->getRepository(Publication::class)
            ->createQueryBuilder('p')
            ->select('COUNT(p.idPublication)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function countByVerified(EntityManagerInterface $em, bool $verified): int
    {
        return (int) $em->getRepository(Publication::class)
            ->createQueryBuilder('p')
            ->select('COUNT(p.idPublication)')
            ->where('p.estVerifie = :v')
            ->setParameter('v', $verified)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getTypeCibles(): array
    {
        return ['HEBERGEMENT', 'ACTIVITE', 'TRANSPORT'];
    }

    private function getStatuts(): array
    {
        return ['VALIDE', 'EN_ATTENTE', 'REFUSE'];
    }
}
