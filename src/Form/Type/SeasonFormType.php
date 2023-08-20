<?php

namespace App\Form\Type;

use App\Form\Model\SeasonDto;
use App\Form\Model\DirectorDto;
use App\Form\Type\EpisodeFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class SeasonFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('seasonNumber', NumberType::class)
            ->add('summary', TextType::class)
            ->add('title', TextType::class)
            ->add('episodes', CollectionType::class, [
                'allow_add' => true,
                'entry_type' => EpisodeFormType::class
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SeasonDto::class,
        ]);
    }

    public function getName(): string
    {
        return '';
    }
}