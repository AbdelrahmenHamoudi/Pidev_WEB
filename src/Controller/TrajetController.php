<?php

namespace App\Controller;

use App\Entity\Trajet;
use App\Repository\TrajetRepository;
use App\Repository\VoitureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[Route('/trajets')]
final class TrajetController extends AbstractController
{
    private const DEFAULT_USER_ID = 32;

    #[Route('', name: 'app_trajet', methods: ['GET'])]
    public function index(Request $request, TrajetRepository $trajetRepo, VoitureRepository $voitureRepo): Response
    {
        $user = $this->getUser();
        $userId = $user ? $user->getId() : self::DEFAULT_USER_ID;

        $search = trim($request->query->get('search', ''));
        $statut = trim($request->query->get('statut', ''));
        $voitureId = $request->query->get('voiture', '');
        $sortBy = $request->query->get('sort', 'date_reservation');
        $order = strtoupper($request->query->get('order', 'DESC'));

        $qb = $trajetRepo->createQueryBuilder('t')
            ->leftJoin('t.id_voiture', 'v')
            ->addSelect('v');

        if ($user) {
            $qb->andWhere('t.id_utilisateur = :userId')
               ->setParameter('userId', $user->getId());
        }

        if ($search) {
            $qb->andWhere('t.point_depart LIKE :search OR t.point_arrivee LIKE :search OR v.marque LIKE :search OR v.modele LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($statut) {
            $qb->andWhere('t.statut = :statut')
                ->setParameter('statut', $statut);
        }

        if ($voitureId !== '' && $voitureId !== null) {
            $qb->andWhere('v.id = :voitureId')
                ->setParameter('voitureId', (int) $voitureId);
        }

        $sortableFields = ['date_reservation', 'point_depart', 'point_arrivee', 'nb_personnes', 'statut'];
        if (!in_array($sortBy, $sortableFields, true)) {
            $sortBy = 'date_reservation';
        }

        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'DESC';
        }

        $qb->orderBy('t.' . $sortBy, $order);
        $trajets = $qb->getQuery()->getResult();
        $voitures = $voitureRepo->findAll();

        return $this->render('frontend/trajet/index.html.twig', [
            'trajets' => $trajets,
            'voitures' => $voitures,
            'search' => $search,
            'statut' => $statut,
            'voitureId' => $voitureId,
            'sortBy' => $sortBy,
            'order' => $order,
        ]);
    }

    #[Route('/ajouter', name: 'app_trajet_create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $em, VoitureRepository $voitureRepo, ValidatorInterface $validator, HubInterface $hub): Response
    {
        $trajet = new Trajet();

        if ($request->isMethod('POST')) {
            $voiture = $voitureRepo->find($request->request->get('id_voiture'));
            if (!$voiture) {
                $this->addFlash('error', 'Veuillez choisir une voiture valide.');
                return $this->redirectToRoute('app_trajet_create');
            }

            $trajet->setIdVoiture($voiture);

            $user = $this->getUser();
            $trajet->setIdUtilisateur($user ? $user->getId() : self::DEFAULT_USER_ID);

            $trajet->setPointDepart($request->request->get('point_depart'));
            $trajet->setPointArrivee($request->request->get('point_arrivee'));
            $trajet->setDistanceKm((float) $request->request->get('distance_km'));

            $dateString = $request->request->get('date_reservation');
            $trajet->setDateReservation($dateString ? new \DateTime($dateString) : null);

            $trajet->setNbPersonnes((int) $request->request->get('nb_personnes', 1));
            $trajet->setStatut($request->request->get('statut', 'Réservé'));

            $errors = $validator->validate($trajet);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            } else {
                $em->persist($trajet);
                $em->flush();

                $update = new Update(
                    'https://re7la.com/admin/trajets',
                    json_encode([
                        'action' => 'add_trajet',
                        'message' => "Nouveau trajet réservé : {$trajet->getPointDepart()} vers {$trajet->getPointArrivee()}",
                        'voiture_marque' => $voiture->getMarque(),
                        'voiture_modele' => $voiture->getModele()
                    ])
                );
                
                try {
                    $hub->publish($update);
                } catch (\Exception $e) {
                    // Ignore silently if Mercure is down
                }

                $this->addFlash('success', 'Trajet ajouté avec succès !');
                return $this->redirectToRoute('app_trajet');
            }
        }

        $voitures = $voitureRepo->findAll();

        return $this->render('frontend/trajet/form.html.twig', [
            'trajet' => $trajet,
            'voitures' => $voitures,
            'title' => 'Réserver un trajet',
        ]);
    }

    #[Route('/{id}', name: 'app_trajet_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Trajet $trajet): Response
    {
        return $this->render('frontend/trajet/show.html.twig', [
            'trajet' => $trajet,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_trajet_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Trajet $trajet, EntityManagerInterface $em, VoitureRepository $voitureRepo, ValidatorInterface $validator): Response
    {
        if ($request->isMethod('POST')) {
            $voiture = $voitureRepo->find($request->request->get('id_voiture'));
            if ($voiture) {
                $trajet->setIdVoiture($voiture);
            }

            $trajet->setPointDepart($request->request->get('point_depart'));
            $trajet->setPointArrivee($request->request->get('point_arrivee'));
            $trajet->setDistanceKm((float) $request->request->get('distance_km'));

            $dateString = $request->request->get('date_reservation');
            $trajet->setDateReservation($dateString ? new \DateTime($dateString) : null);

            $trajet->setNbPersonnes((int) $request->request->get('nb_personnes', 1));
            $trajet->setStatut($request->request->get('statut', 'Réservé'));

            $errors = $validator->validate($trajet);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            } else {
                $em->flush();
                $this->addFlash('success', 'Trajet modifié avec succès !');
                return $this->redirectToRoute('app_trajet');
            }
        }

        $voitures = $voitureRepo->findAll();

        return $this->render('frontend/trajet/form.html.twig', [
            'trajet' => $trajet,
            'voitures' => $voitures,
            'title' => 'Modifier le trajet',
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_trajet_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Trajet $trajet, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $trajet->getId(), $request->request->get('_csrf_token'))) {
            $em->remove($trajet);
            $em->flush();
            $this->addFlash('success', 'Trajet supprimé avec succès !');
        }

        return $this->redirectToRoute('app_trajet');
    }
}
