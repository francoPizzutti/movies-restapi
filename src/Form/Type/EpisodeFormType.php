<?php

namespace App\Form\Type;

use App\Form\Model\EpisodeDto;
use App\Form\Model\DirectorDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class EpisodeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('directorId', NumberType::class)
            ->add('episodeNumber', TextType::class)
            ->add('releaseDate', TextType::class)
            ->add('title', TextType::class)
            ->add('summary', TextType::class)
            ->add('invitedActors', CollectionType::class, [
                'allow_add' => true,
                'entry_type' => NumberType::class
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EpisodeDto::class,
        ]);
    }

    public function getName(): string
    {
        return '';
    }
}