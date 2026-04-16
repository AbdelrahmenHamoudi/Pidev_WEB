<?php

namespace App\Form;

use App\Entity\Activite;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Range;

class ActiviteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomA', TextType::class, [
                'label' => "Nom de l'activité",
                'attr'  => ['placeholder' => 'Ex: Randonnée au Djebel Zaghouan'],
                'constraints' => [
                    new NotBlank(message: 'Le nom est obligatoire'),
                    new Length(
                        min: 3, minMessage: 'Le nom doit avoir au moins {{ limit }} caractères',
                        max: 255, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ),
                ],
            ])
            ->add('descriptionA', TextareaType::class, [
                'label' => 'Description',
                'attr'  => ['rows' => 4, 'placeholder' => 'Décrivez l\'activité...'],
                'constraints' => [
                    new NotBlank(message: 'La description est obligatoire'),
                    new Length(
                        min: 10,
                        minMessage: 'La description doit avoir au moins {{ limit }} caractères'
                    ),
                ],
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
                'attr'  => ['placeholder' => 'Ex: Sousse, Djerba...'],
                'constraints' => [
                    new NotBlank(message: 'Le lieu est obligatoire'),
                ],
            ])
            ->add('prixParPersonne', NumberType::class, [
                'label' => 'Prix par personne (DT)',
                'attr'  => ['placeholder' => '0.00', 'min' => 0],
                'constraints' => [
                    new NotBlank(message: 'Le prix est obligatoire'),
                    new Positive(message: 'Le prix doit être positif'),
                ],
            ])
            ->add('capaciteMax', NumberType::class, [
                'label' => 'Capacité maximale',
                'attr'  => ['min' => 1],
                'constraints' => [
                    new NotBlank(message: 'La capacité est obligatoire'),
                    new Range([
                        'min' => 1,
                        'max' => 1000,
                        'notInRangeMessage' => 'Entre {{ min }} et {{ max }}'
                    ]),
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label'   => "Type d'activité",
                'placeholder' => '-- Choisir un type --',
                'choices' => [
                    'Aventure'  => 'Aventure',
                    'Sport'     => 'Sport',
                    'Culture'   => 'Culture',
                    'Detente'   => 'Detente',
                    'Excursion' => 'Excursion',
                ],
                'constraints' => [
                    new NotBlank(message: 'Le type est obligatoire'),
                ],
            ])
            ->add('statut', ChoiceType::class, [
                'label'   => 'Statut',
                'choices' => [
                    'Disponible' => 'Disponible',
                    'Complet'    => 'Complet',
                    'Annule'     => 'Annule',
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label'    => 'Image (JPG, PNG — max 2Mo)',
                'mapped'   => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize'          => '2M',
                        'maxSizeMessage'   => 'L\'image ne doit pas dépasser 2 Mo',
                        'mimeTypes'        => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Format invalide. JPG, PNG ou WEBP uniquement.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Activite::class]);
    }
}
