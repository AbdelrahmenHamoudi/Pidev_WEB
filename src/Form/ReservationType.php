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
                'label' => 'form.accommodation',
                'placeholder' => 'form.select_accommodation',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'validation.not_blank_accommodation'])
                ]
            ])
            ->add('dateDebutR', DateType::class, [
                'label' => 'form.check_in',
                'widget' => 'single_text',
                'input' => 'datetime',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'validation.not_blank_checkin']),
                    new Assert\GreaterThanOrEqual([
                        'value' => 'today',
                        'message' => 'validation.date_future'
                    ])
                ],
                'attr' => ['min' => (new \DateTime())->format('Y-m-d')]
            ])
            ->add('dateFinR', DateType::class, [
                'label' => 'form.check_out',
                'widget' => 'single_text',
                'input' => 'datetime',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'validation.not_blank_checkout'])
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
                            'validation.date_after'
                        ));
                    }

                    $diff = $debut->diff($fin);
                    if ($diff->days > 30) {
                        $form->get('dateFinR')->addError(new \Symfony\Component\Form\FormError(
                            'validation.max_nights'
                        ));
                    }

                    // Check if at least 1 night
                    if ($diff->days < 1) {
                        $form->get('dateFinR')->addError(new \Symfony\Component\Form\FormError(
                            'validation.min_nights'
                        ));
                    }
                }

                $hebergement = $reservation->getHebergement();
                if ($hebergement && !$hebergement->isDisponibleHeberg()) {
                    $form->get('hebergement')->addError(new \Symfony\Component\Form\FormError(
                        'validation.not_available'
                    ));
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
