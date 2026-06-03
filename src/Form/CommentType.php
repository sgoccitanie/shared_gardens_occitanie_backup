<?php

namespace App\Form;

use App\Entity\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security;

class CommentType extends AbstractType
{
    public function __construct(private Security $security) {}

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $isAdminOrEditor =
            $this->security->isGranted('ROLE_ADMIN')
            || $this->security->isGranted('ROLE_EDITOR');

        $builder
            ->add('pseudo', TextType::class, [
                'label' => 'Votre pseudo',
                'required' => !$isAdminOrEditor,
                'attr' => [
                    'maxlength' => 100,
                    'placeholder' => 'Entrez votre pseudo',
                    'class' => 'form-control border-0 shadow-none p-3',
                    'style' => $isAdminOrEditor ? 'display:none;' : ''
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Votre commentaire',
                'attr' => [
                    'rows' => 5,
                    'style' => 'width: 100%; margin-top: 0px;'
                ],
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
            'show_pseudo' => true,
        ]);
    }
}
