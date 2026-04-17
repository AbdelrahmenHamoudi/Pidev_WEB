<?php

namespace App\Form;

use App\Entity\Hebergement;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ReservationType extends AbstractType
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('hebergement', EntityType::class, [
                'class' => Hebergement::class,
                'choice_label' => function (Hebergement $h) {
                    return sprintf('%s - %s DT/nuit (%d pers.)', 
                        $h->getTitre(), 
                        $h->getPrixParNuit(),
                        $h->getCapacite()
                    );
                },
                'query_builder' => function () {
                    return $this->em->getRepository(Hebergement::class)
                        ->createQueryBuilder('h')
                        ->where('h.disponible_heberg = :dispo')
                        ->setParameter('dispo', true)
                        ->orderBy('h.titre', 'ASC');
                },
                'label' => 'Hébergement',
                'placeholder' => 'Sélectionnez un hébergement',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner un hébergement'])
                ]
            ])
            ->add('dateDebutR', DateType::class, [
                'label' => 'Date d\'arrivée',
                'widget' => 'single_text',
                'input' => 'datetime',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date d\'arrivée est obligatoire']),
                    new Assert\GreaterThanOrEqual([
                        'value' => 'today',
                        'message' => 'La date doit être aujourd\'hui ou dans le futur'
                    ])
                ],
                'attr' => ['min' => (new \DateTime())->format('Y-m-d')]
            ])
            ->add('dateFinR', DateType::class, [
                'label' => 'Date de départ',
                'widget' => 'single_text',
                'input' => 'datetime',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de départ est obligatoire'])
                ],
                'attr' => ['min' => (new \DateTime())->format('Y-m-d')]
            ]);

        // Post-submit validation
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $reservation = $event->getData();
            $form = $event->getForm();

            if ($reservation instanceof Reservation) {
                $debut = $reservation->getDateDebutR();
                $fin = $reservation->getDateFinR();

                if ($debut && $fin) {
                    if ($fin <= $debut) {
                        $form->get('dateFinR')->addError(new \Symfony\Component\Form\FormError(
                            'La date de départ doit être après la date d\'arrivée'
                        ));
                    }

                    $diff = $debut->diff($fin);
                    if ($diff->days > 30) {
                        $form->get('dateFinR')->addError(new \Symfony\Component\Form\FormError(
                            'La réservation ne peut pas dépasser 30 nuits'
                        ));
                    }

                    // Check if at least 1 night
                    if ($diff->days < 1) {
                        $form->get('dateFinR')->addError(new \Symfony\Component\Form\FormError(
                            'La réservation doit être d\'au moins 1 nuit'
                        ));
                    }
                }

                $hebergement = $reservation->getHebergement();
                if ($hebergement && !$hebergement->isDisponibleHeberg()) {
                    $form->get('hebergement')->addError(new \Symfony\Component\Form\FormError(
                        'Cet hébergement n\'est pas disponible actuellement'
                    ));
                }

                // CONTROLE DE SAISIE: Overlapping reservations
                if ($hebergement && $debut && $fin) {
                    $excludeId = null;
                    try {
                        $excludeId = $reservation->getId_reservation();
                    } catch (\Error $e) {
                        // Uninitialized string property means it's a new reservation
                    }

                    $conflicts = $this->em->getRepository(Reservation::class)->findOverlappingReservations(
                        $hebergement->getIdHebergement(),
                        $debut,
                        $fin,
                        $excludeId
                    );
                    
                    if (!empty($conflicts)) {
                        $conflict = $conflicts[0];
                        $form->get('dateDebutR')->addError(new \Symfony\Component\Form\FormError(
                            sprintf('Indisponible : cet hébergement est déjà réservé du %s au %s.', 
                                $conflict->getDateDebutR()->format('d/m/Y'), 
                                $conflict->getDateFinR()->format('d/m/Y')
                            )
                        ));
                    }
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}
