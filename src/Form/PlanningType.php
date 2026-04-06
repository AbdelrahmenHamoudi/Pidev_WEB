<?php

namespace App\Form;

use App\Entity\Planningactivite;
use App\Entity\Activite;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlanningType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('activite', EntityType::class, [
                'class'        => Activite::class,
                'choice_label' => 'nomA',
                'label'        => 'Activite',
                'placeholder'  => '-- Choisir une activite --',
            ])
            ->add('datePlanning', DateType::class, [
                'label'  => 'Date',
                'widget' => 'single_text',
                'attr'   => ['min' => (new \DateTime())->format('Y-m-d')],
            ])
            ->add('heureDebut', TextType::class, [
                'label' => 'Heure de debut',
                'attr'  => [
                    'placeholder' => '08:00',
                    'pattern'     => '[0-9]{2}:[0-9]{2}',
                    'title'       => 'Format HH:MM (ex: 08:00)',
                ],
            ])
            ->add('heureFin', TextType::class, [
                'label' => 'Heure de fin',
                'attr'  => [
                    'placeholder' => '10:00',
                    'pattern'     => '[0-9]{2}:[0-9]{2}',
                    'title'       => 'Format HH:MM (ex: 10:00)',
                ],
            ])
            ->add('nbPlacesRestantes', IntegerType::class, [
                'label' => 'Nombre de places',
                'attr'  => ['min' => 0],
            ])
            ->add('etat', ChoiceType::class, [
                'label'   => 'Etat',
                'choices' => [
                    'Disponible' => 'Disponible',
                    'Complet'    => 'Complet',
                    'Annule'     => 'Annule',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Planningactivite::class]);
    }
}