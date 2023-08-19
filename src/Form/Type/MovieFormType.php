<?php

namespace App\Form\Type;

use App\Entity\Movie;
use App\Form\Model\MovieDto;
use App\Form\Model\DirectorDto;
use App\Form\Type\ActorFormType;
use App\Form\Type\DirectorFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;

class MovieFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('genre', TextType::class)
            ->add('duration', NumberType::class)
            ->add('directorId', NumberType::class)
            ->add('actorIds', CollectionType::class, [
                'allow_add' => true,
                'entry_type' => NumberType::class
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MovieDto::class,
        ]);
    }

    public function getName(): string
    {
        return '';
    }
}