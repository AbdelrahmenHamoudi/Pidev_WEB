<?php

namespace App\Form;

use App\Entity\Hebergement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class HebergementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le titre est obligatoire']),
                    new Assert\Length([
                        'min' => 5,
                        'max' => 255,
                        'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le titre ne doit pas dépasser {{ limit }} caractères'
                    ])
                ],
                'attr' => ['placeholder' => 'Ex: Villa luxe Hammamet']
            ])
            ->add('descHebergement', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'La description ne doit pas dépasser {{ limit }} caractères'
                    ])
                ],
                'attr' => ['rows' => 3]
            ])
            ->add('typeHebergement', ChoiceType::class, [
                'label' => 'Type d\'hébergement',
                'choices' => [
                    'Villa' => 'Villa',
                    'Appartement' => 'Appartement',
                    'Maison' => 'Maison',
                    'Hotel' => 'Hotel',
                    'Maison d\'hôte' => 'Maison d\'hôte'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le type est obligatoire']),
                    new Assert\Choice([
                        'choices' => ['Villa', 'Appartement', 'Maison', 'Hotel', 'Maison d\'hôte'],
                        'message' => 'Veuillez sélectionner un type valide'
                    ])
                ]
            ])
            ->add('capacite', IntegerType::class, [
                'label' => 'Capacité (personnes)',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La capacité est obligatoire']),
                    new Assert\Positive(['message' => 'La capacité doit être positive']),
                    new Assert\LessThanOrEqual([
                        'value' => 100,
                        'message' => 'La capacité maximale est de {{ compared_value }}'
                    ])
                ],
                'attr' => ['min' => 1, 'max' => 100]
            ])
            ->add('prixParNuit', MoneyType::class, [
                'label' => 'Prix par nuit (DT)',
                'currency' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prix est obligatoire']),
                    new Assert\Positive(['message' => 'Le prix doit être positif']),
                    new Assert\LessThanOrEqual([
                        'value' => 10000,
                        'message' => 'Le prix maximum est de {{ compared_value }} DT'
                    ])
                ],
                'attr' => ['min' => 0, 'step' => '0.01']
            ])
            ->add('disponible', ChoiceType::class, [
                'label' => 'Disponibilité',
                'choices' => [
                    'Disponible' => true,
                    'Non disponible' => false
                ]
            ])
            ->add('imageFiles', FileType::class, [
                'label' => 'Photos (plusieurs possibles)',
                'multiple' => true,
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Assert\All([
                        new Assert\Image([
                            'maxSize' => '5M',
                            'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                            'mimeTypesMessage' => 'Formats acceptés: JPEG, PNG, WEBP',
                        ])
                    ])
                ],
                'attr' => [
                    'accept' => 'image/*',
                    'class' => 'form-control'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Hebergement::class,
        ]);
    }
}
