<?php

namespace App\Controller;

use App\Entity\Activite;
use App\Form\ActiviteType;
use App\Service\ActiviteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/activites')]
class ActiviteController extends AbstractController
{
    public function __construct(private ActiviteService $service) {}

    // ══════════════════════════════════════════════════════
    //  BACK OFFICE
    // ══════════════════════════════════════════════════════

    // ── Liste ──────────────────────────────────────────────
    #[Route('/', name: 'activite_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $type    = $request->query->get('type');
        $prixMax = $request->query->get('prix_max');

        $activites = ($type || $prixMax)
            ? $this->service->findByFilters($type, $prixMax ? (float)$prixMax : null)
            : $this->service->findAll();

        return $this->render('backOffice/admin/activite/index.html.twig', [
            'activites'  => $activites,
            'filterType' => $type,
            'filterPrix' => $prixMax,
        ]);
    }

    // ── Creer — DOIT être avant /{id} ─────────────────────
    #[Route('/new', name: 'activite_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $activite = new Activite();
        $form     = $this->createForm(ActiviteType::class, $activite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            $this->service->create($activite, $imageFile);

            $this->addFlash('success', "Activite \"{$activite->getNomA()}\" creee avec succes !");
            return $this->redirectToRoute('activite_index');
        }

        return $this->render('backOffice/admin/activite/new.html.twig', [
            'form'     => $form,
            'activite' => $activite,
        ]);
    }

    // ── Details ────────────────────────────────────────────
    #[Route('/{id}', name: 'activite_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $activite = $this->service->findById($id);

        if (!$activite) {
            throw $this->createNotFoundException("Activite #$id introuvable");
        }

        return $this->render('backOffice/admin/activite/show.html.twig', [
            'activite' => $activite,
        ]);
    }

    // ── Modifier ───────────────────────────────────────────
    #[Route('/{id}/edit', name: 'activite_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $activite = $this->service->findById($id);

        if (!$activite) {
            throw $this->createNotFoundException("Activite #$id introuvable");
        }

        $form = $this->createForm(ActiviteType::class, $activite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            $this->service->update($activite, $imageFile);

            $this->addFlash('success', "Activite modifiee avec succes !");
            return $this->redirectToRoute('activite_show', ['id' => $id]);
        }

        return $this->render('backOffice/admin/activite/edit.html.twig', [
            'form'     => $form,
            'activite' => $activite,
        ]);
    }

    // ── Supprimer ──────────────────────────────────────────
    #[Route('/{id}/delete', name: 'activite_delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $activite = $this->service->findById($id);

        if (!$activite) {
            throw $this->createNotFoundException("Activite #$id introuvable");
        }
        if ($this->isCsrfTokenValid('delete_activite_' . $id, $request->request->get('_token'))) {
            $nom = $activite->getNomA();
            $this->service->delete($activite);
            $this->addFlash('success', "Activite \"$nom\" supprimee.");
        } else {
            $this->addFlash('error', 'Token de securite invalide.');
        }

        return $this->redirectToRoute('activite_index');
    }

    // ══════════════════════════════════════════════════════
    //  FRONT OFFICE
    //  IMPORTANT : /catalogue/new AVANT /catalogue/{id}
    // ══════════════════════════════════════════════════════

    // ── Catalogue (liste publique) ─────────────────────────
    #[Route('/catalogue', name: 'activite_index_front', methods: ['GET'])]
    public function indexFront(Request $request): Response
    {
        $type    = $request->query->get('type');
        $prixMax = $request->query->get('prix_max');

        $activites = ($type || $prixMax)
            ? $this->service->findByFilters($type, $prixMax ? (float)$prixMax : null)
            : $this->service->findDisponibles();

        return $this->render('frontend/activite/index.html.twig', [
            'activites'  => $activites,
            'filterType' => $type,
            'filterPrix' => $prixMax,
        ]);
    }

    // ── Detail public ──────────────────────────────────────
    #[Route('/catalogue/{id}', name: 'activite_show_front', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function showFront(int $id): Response
    {
        $activite = $this->service->findById($id);

        if (!$activite) {
            throw $this->createNotFoundException("Activite introuvable");
        }

        return $this->render('frontend/activite/show.html.twig', [
            'activite' => $activite,
        ]);
    }
}