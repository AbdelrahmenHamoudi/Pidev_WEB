<?php

namespace App\Form;

use App\Entity\Activite;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ActiviteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomA', TextType::class, [
                'label' => 'Nom de l\'activite',
                'attr'  => ['placeholder' => 'Ex: Randonnee au Djebel Zaghouan'],
            ])
            ->add('descriptionA', TextareaType::class, [
                'label' => 'Description',
                'attr'  => ['rows' => 5, 'placeholder' => 'Decrivez l\'activite...'],
            ])
            ->add('lieu', TextType::class, [
                'label' => 'Lieu',
                'attr'  => ['placeholder' => 'Ex: Sousse, Djerba...'],
            ])
            ->add('prixParPersonne', MoneyType::class, [
                'label'    => 'Prix par personne',
                'currency' => 'TND',
            ])
            ->add('capaciteMax', NumberType::class, [
                'label' => 'Capacite maximale',
                'attr'  => ['min' => 1],
            ])
            
            ->add('imageFile', FileType::class, [
                'label'    => 'Image (JPG, PNG)',
                'mapped'   => false,   // pas lie a l'entite directement
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize'   => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Format invalide. JPG, PNG ou WEBP uniquement.',
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Activite::class]);
    }
}
