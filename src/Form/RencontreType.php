<?php

namespace App\Form;

use App\Entity\Equipe;
use App\Entity\Rencontre;
use App\Entity\Terrains;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RencontreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('round')
            ->add('points')
            ->add('terrains', EntityType::class, [
                'class' => Terrains::class,
                'choice_label' => 'id',
            ])
            ->add('equipes', EntityType::class, [
                'class' => Equipe::class,
                'choice_label' => 'id',
            ])
            ->add('equipeVisiteur', EntityType::class, [
                'class' => Equipe::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rencontre::class,
        ]);
    }
}
