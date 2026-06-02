<?php

namespace App\Form;

use App\Entity\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('pseudo', TextType::class, [
                'label' => 'Votre pseudo',
                'required' => true,
                'label_attr' => [
                    'class' => 'me-2 mb-0'
                ],
                'attr' => [
                    'maxlength' => 100,
                    'placeholder' => 'Entrez votre pseudo',
                    'class' => 'form-control border-0 shadow-none p-3'
                ],
            ])

            ->add('content', TextareaType::class, [
                'label' => 'Votre commentaire',
                'attr' => [
                    'rows' => 5,
                    'style' => 'width: 100%;'
                ],
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
        ]);
    }
}
