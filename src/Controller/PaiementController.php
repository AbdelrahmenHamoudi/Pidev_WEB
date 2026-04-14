<?php

namespace App\Controller;

use App\Entity\Trajet;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/paiement')]
final class PaiementController extends AbstractController
{
    #[Route('/checkout/{id}', name: 'app_paiement_checkout', requirements: ['id' => '\d+'])]
    public function checkout(Trajet $trajet): Response
    {
        // If already paid, redirect
        if ($trajet->getStatut() === 'Payé') {
            $this->addFlash('info', 'Ce trajet a déjà été réglé.');
            return $this->redirectToRoute('app_trajet');
        }

        return $this->render('frontend/paiement/checkout.html.twig', [
            'trajet' => $trajet,
            'amount' => $trajet->getDistanceKm() * ($trajet->getIdVoiture() ? $trajet->getIdVoiture()->getPrixKM() : 0.5),
        ]);
    }

    #[Route('/process/{id}', name: 'app_paiement_process', methods: ['POST'])]
    public function process(Trajet $trajet, EntityManagerInterface $em): Response
    {
        $trajet->setStatut('Payé');
        $em->flush();

        return $this->json(['status' => 'success', 'redirect' => $this->generateUrl('app_paiement_success', ['id' => $trajet->getId()])]);
    }

    #[Route('/d17/{id}', name: 'app_paiement_d17', methods: ['POST'])]
    public function d17(Trajet $trajet, EntityManagerInterface $em): Response
    {
        // Simulate D17 verification
        $trajet->setStatut('Payé (D17)');
        $em->flush();

        return $this->json(['status' => 'success', 'redirect' => $this->generateUrl('app_paiement_success', ['id' => $trajet->getId()])]);
    }

    #[Route('/later/{id}', name: 'app_paiement_later', methods: ['POST'])]
    public function later(Trajet $trajet, EntityManagerInterface $em): Response
    {
        $trajet->setStatut('En attente de paiement');
        $em->flush();

        return $this->redirectToRoute('app_paiement_success', ['id' => $trajet->getId()]);
    }

    #[Route('/success/{id}', name: 'app_paiement_success')]
    public function success(Trajet $trajet): Response
    {
        return $this->render('frontend/paiement/success.html.twig', [
            'trajet' => $trajet,
        ]);
    }

    #[Route('/cancel/{id}', name: 'app_paiement_cancel', methods: ['POST'])]
    public function cancel(Trajet $trajet, EntityManagerInterface $em): Response
    {
        // Reset status back to pending if it was not yet paid
        if (!str_contains($trajet->getStatut(), 'Payé')) {
            $trajet->setStatut('En attente');
            $em->flush();
        }

        $this->addFlash('warning', 'Paiement annulé. Votre trajet est maintenant en attente.');
        return $this->redirectToRoute('app_trajet');
    }
}
