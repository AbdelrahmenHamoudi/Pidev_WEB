<?php

namespace App\Form;

use App\Entity\Promotion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PromotionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label'      => 'Titre de la promotion',
                'empty_data' => '',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le titre est obligatoire.', 'normalizer' => 'trim']),
                    new Assert\Length([
                        'min'        => 3,
                        'max'        => 255,
                        'minMessage' => 'Minimum {{ limit }} caractères.',
                        'maxMessage' => 'Maximum {{ limit }} caractères.',
                    ]),
                ],
                'attr' => ['placeholder' => 'Ex: Offre Été 2025', 'minlength' => 3, 'maxlength' => 255],
            ])
            ->add('description', TextareaType::class, [
                'label'      => 'Description',
                'required'   => false,
                'empty_data' => null,
                'constraints' => [
                    new Assert\Length(['max' => 1000, 'maxMessage' => 'Maximum {{ limit }} caractères.']),
                ],
                'attr' => ['rows' => 3, 'placeholder' => 'Décrivez votre promotion...'],
            ])
            ->add('promoType', ChoiceType::class, [
                'label'   => 'Type de promotion',
                'choices' => [
                    '🎫 Individuelle (1 offre)'  => 'individuelle',
                    '📦 Pack (plusieurs offres)' => 'pack',
                ],
                'attr' => ['id' => 'promoTypeSelect'],
            ])
            ->add('discountPercentage', NumberType::class, [
                'label'      => 'Réduction (%)',
                'required'   => false,
                'scale'      => 2,
                'empty_data' => null,
                'constraints' => [
                    new Assert\Range([
                        'min'               => 1,
                        'max'               => 100,
                        'notInRangeMessage' => 'Entre {{ min }}% et {{ max }}%.',
                    ]),
                ],
                'attr' => ['placeholder' => 'Ex: 20', 'min' => 1, 'max' => 100, 'step' => '0.01'],
            ])
            ->add('discountFixed', NumberType::class, [
                'label'      => 'Réduction fixe (DT)',
                'required'   => false,
                'scale'      => 2,
                'empty_data' => null,
                'constraints' => [
                    new Assert\PositiveOrZero(['message' => 'Doit être positif.']),
                ],
                'attr' => ['placeholder' => 'Ex: 50', 'min' => 0, 'step' => '0.01'],
            ])
            ->add('startDate', DateType::class, [
                'label'       => 'Date de début',
                'widget'      => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de début est obligatoire.']),
                ],
            ])
            ->add('endDate', DateType::class, [
                'label'       => 'Date de fin',
                'widget'      => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de fin est obligatoire.']),
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label'    => 'Image de la promotion',
                'mapped'   => false,
                'required' => false,
                'attr'     => [
                    'accept' => '.jpg,.jpeg,.png,.webp,.gif',
                ],
            ])
            ->add('isVerrouille', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                'required' => false,
                'label' => 'Promotion verrouillée'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Promotion::class]);
    }
}