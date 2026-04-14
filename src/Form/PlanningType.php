<?php

namespace App\Form;

use App\Entity\Activite;
use App\Entity\Planningactivite;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class PlanningType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('activite', EntityType::class, [
                'class'        => Activite::class,
                'choice_label' => 'nomA',
                'label'        => 'Activité',
                'placeholder'  => '-- Choisir une activité --',
                'constraints'  => [
                    new NotNull(message: "L'activité est obligatoire"),
                ],
            ])
            ->add('datePlanning', DateType::class, [
                'label'       => 'Date',
                'widget'      => 'single_text',
                'attr'        => ['min' => (new \DateTime())->format('Y-m-d')],
                'constraints' => [
                    new NotNull(message: 'La date est obligatoire'),
                    new GreaterThanOrEqual(
                        value: 'today',
                        message: 'La date doit être aujourd\'hui ou dans le futur'
                    ),
                ],
            ])
            ->add('heureDebut', TextType::class, [
                'label'       => 'Heure de début',
                'attr'        => [
                    'placeholder' => 'HH:MM',
                    'pattern'     => '[0-9]{2}:[0-9]{2}',
                ],
                'constraints' => [
                    new NotBlank(message: "L'heure de début est obligatoire"),
                ],
            ])
            ->add('heureFin', TextType::class, [
                'label'       => 'Heure de fin',
                'attr'        => [
                    'placeholder' => 'HH:MM',
                    'pattern'     => '[0-9]{2}:[0-9]{2}',
                ],
                'constraints' => [
                    new NotBlank(message: "L'heure de fin est obligatoire"),
                ],
            ])
            ->add('nbPlacesRestantes', IntegerType::class, [
                'label'       => 'Nombre de places',
                'attr'        => ['min' => 0],
                'constraints' => [
                    new PositiveOrZero(message: 'Le nombre de places ne peut pas être négatif'),
                ],
            ])
            ->add('etat', ChoiceType::class, [
                'label'   => 'État',
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