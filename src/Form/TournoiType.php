<?php

namespace App\Form;

use App\Entity\Tournoi;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TournoiType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('sport')
            ->add('participant', Integer::class)
            ->add('format', ChoiceType::class, [
                'choices' => [
                    'Élimination simple' => 'elimination_simple',
                    'Double élimination' => 'double_elimination',
                    'Round robin' => 'round_robin',
                    'Libre' => 'libre',
                ],
                'placeholder' => 'Choisir un format',
                'required' => false,
            ])
            ->add('dateDebut')
            ->add('dateFin')
            ->add('visibility', ChoiceType::class, [
                'choices' => [
                    'Public' => 'public',
                    'Privé' => 'private',
                    'Lien uniquement' => 'unlisted',
                ],
                'placeholder' => 'Choisir une visibilité',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tournoi::class,
        ]);
    }
}
