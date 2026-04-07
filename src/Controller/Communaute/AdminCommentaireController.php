<?php

namespace App\Controller\Communaute;

use App\Entity\Commentaire;
use App\Entity\Publication;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/commentaires')]
class AdminCommentaireController extends AbstractController
{
    // ================================================================
    // LIST ALL
    // ================================================================

    #[Route('', name: 'app_admin_commentaire_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        [$commentaires, $search, $statut] = $this->getFilteredCommentaires($request, $em);

        return $this->render('backendCommunaute/commentaire/index.html.twig', [
            'commentaires'  => $commentaires,
            'publication'   => null,
            'search'        => $search,
            'statut'        => $statut,
            'totalCommentaires' => $this->countCommentaires($em),
            'totalActifs'       => $this->countByStatut($em, true),
            'totalInactifs'     => $this->countByStatut($em, false),
        ]);
    }

    #[Route('/ajax/list', name: 'app_admin_commentaire_ajax_list', methods: ['GET'])]
    public function ajaxList(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        [$commentaires, $search, $statut] = $this->getFilteredCommentaires($request, $em);

        return $this->render('backendCommunaute/commentaire/_table.html.twig', [
            'commentaires' => $commentaires,
            'publication'  => null,
            'search'       => $search,
            'statut'       => $statut,
        ]);
    }

    // ================================================================
    // SHOW
    // ================================================================

    #[Route('/{idCommentaire}', name: 'app_admin_commentaire_show', methods: ['GET'])]
    public function show(Commentaire $commentaire): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('backendCommunaute/commentaire/show.html.twig', [
            'commentaire' => $commentaire,
        ]);
    }

    // ================================================================
    // CREATE
    // ================================================================

    #[Route('/new', name: 'app_admin_commentaire_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($request->isMethod('POST')) {
            $errors = $this->validateCommentaireForm($request, $em);

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('app_admin_commentaire_new');
            }

            $commentaire = new Commentaire();
            $this->hydrateCommentaire($commentaire, $request, $em);

            $em->persist($commentaire);
            $em->flush();

            $this->addFlash('success', '✅ Commentaire créé avec succès.');
            return $this->redirectToRoute('app_admin_commentaire_index');
        }

        return $this->render('backendCommunaute/commentaire/new.html.twig', [
            'publications' => $em->getRepository(Publication::class)->findAll(),
            'users'        => $em->getRepository(Users::class)->findAll(),
        ]);
    }

    // ================================================================
    // EDIT
    // ================================================================

    #[Route('/{idCommentaire}/edit', name: 'app_admin_commentaire_edit', methods: ['GET', 'POST'])]
    public function edit(
        Commentaire $commentaire,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($request->isMethod('POST')) {
            $errors = $this->validateCommentaireForm($request, $em);

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('app_admin_commentaire_edit', [
                    'idCommentaire' => $commentaire->getIdCommentaire(),
                ]);
            }

            $this->hydrateCommentaire($commentaire, $request, $em);
            $em->flush();

            $this->addFlash('success', '✅ Commentaire modifié avec succès.');
            return $this->redirectToRoute('app_admin_commentaire_index');
        }

        return $this->render('backendCommunaute/commentaire/edit.html.twig', [
            'commentaire'  => $commentaire,
            'publications' => $em->getRepository(Publication::class)->findAll(),
            'users'        => $em->getRepository(Users::class)->findAll(),
        ]);
    }

    // ================================================================
    // DELETE
    // ================================================================

    #[Route('/{idCommentaire}/delete', name: 'app_admin_commentaire_delete', methods: ['POST'])]
    public function delete(Commentaire $commentaire, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $em->remove($commentaire);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Commentaire supprimé avec succès.',
        ]);
    }

    // ================================================================
    // TOGGLE STATUT
    // ================================================================

    #[Route('/{idCommentaire}/toggle-statut', name: 'app_admin_commentaire_toggle_statut', methods: ['POST'])]
    public function toggleStatut(Commentaire $commentaire, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $commentaire->setStatutC(!$commentaire->getStatutC());
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'statut'  => $commentaire->getStatutC(),
            'message' => $commentaire->getStatutC()
                ? 'Commentaire activé.'
                : 'Commentaire désactivé.',
        ]);
    }

    // ================================================================
    // PRIVATE HELPERS
    // ================================================================

    private function validateCommentaireForm(Request $request, EntityManagerInterface $em): array
    {
        $errors = [];

        $contenu       = trim((string) $request->request->get('contenuC', ''));
        $publicationId = (int) $request->request->get('idPublication', 0);
        $userId        = (int) $request->request->get('id_utilisateur', 0);

        // Contenu
        if ($contenu === '') {
            $errors[] = '💬 Le contenu est requis.';
        } elseif (mb_strlen($contenu) < 2) {
            $errors[] = '💬 Minimum 2 caractères pour le contenu.';
        } elseif (mb_strlen($contenu) > 200) {
            $errors[] = '💬 Le contenu ne peut pas dépasser 200 caractères.';
        }

        // Publication
        if ($publicationId === 0) {
            $errors[] = '📄 Veuillez sélectionner une publication.';
        } elseif ($em->getRepository(Publication::class)->find($publicationId) === null) {
            $errors[] = '📄 Publication introuvable.';
        }

        // Utilisateur
        if ($userId === 0) {
            $errors[] = '👤 Veuillez sélectionner un utilisateur.';
        } elseif ($em->getRepository(Users::class)->find($userId) === null) {
            $errors[] = '👤 Utilisateur introuvable.';
        }

        return $errors;
    }

    private function hydrateCommentaire(
        Commentaire $commentaire,
        Request $request,
        EntityManagerInterface $em
    ): void {
        $publicationId = (int) $request->request->get('idPublication', 0);
        $userId        = (int) $request->request->get('id_utilisateur', 0);

        $commentaire->setContenuC(trim((string) $request->request->get('contenuC', '')));
        $commentaire->setStatutC((bool) $request->request->get('statutC', false));
        $commentaire->setDateCreationC((new \DateTime())->format('Y-m-d H:i:s'));
        $commentaire->setIdPublication($em->getRepository(Publication::class)->find($publicationId));
        $commentaire->setId_utilisateur($em->getRepository(Users::class)->find($userId));
    }

    private function getFilteredCommentaires(Request $request, EntityManagerInterface $em): array
    {
        $search = trim((string) $request->query->get('search', ''));
        $statut = $request->query->get('statut', '');

        $qb = $em->getRepository(Commentaire::class)
            ->createQueryBuilder('c')
            ->leftJoin('c.idPublication', 'p')
            ->leftJoin('c.id_utilisateur', 'u')
            ->addSelect('p', 'u');

        if ($search !== '') {
            $qb->andWhere('c.contenuC LIKE :search OR u.nom LIKE :search OR u.prenom LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($statut !== '') {
            $qb->andWhere('c.statutC = :statut')
               ->setParameter('statut', (bool) $statut);
        }

        $qb->orderBy('c.idCommentaire', 'DESC');

        return [$qb->getQuery()->getResult(), $search, $statut];
    }

    private function countCommentaires(EntityManagerInterface $em): int
    {
        return (int) $em->getRepository(Commentaire::class)
            ->createQueryBuilder('c')
            ->select('COUNT(c.idCommentaire)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function countByStatut(EntityManagerInterface $em, bool $statut): int
    {
        return (int) $em->getRepository(Commentaire::class)
            ->createQueryBuilder('c')
            ->select('COUNT(c.idCommentaire)')
            ->where('c.statutC = :s')
            ->setParameter('s', $statut)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
