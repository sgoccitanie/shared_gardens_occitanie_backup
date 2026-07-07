<?php

namespace App\Form;

use App\Entity\ContactMessage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\SubjectEmail;

class ContactPageFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Nom et prénom',
                'attr' => [
                    'minlength' => 5,
                    'maxlength' => 100,
                    'required' => true,
                    'placeholder' => 'Votre nom et prénom',
                    'class' => 'form-control input-name',
                ],
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le nom et prénom sont obligatoires',
                    ]),
                    new Length([
                        'min' => 5,
                        'max' => 100,
                        'minMessage' => 'Le nom et prénom doivent contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom et prénom ne doivent pas contenir plus de {{ limit }} caractères',
                    ]),
                ],
            ])

            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'placeholder' => 'Votre email',
                    'class' => 'form-control input-email',
                    'required' => true,
                ],
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'L\'email est obligatoire',
                    ]),
                    new Email([
                        'message' => 'L\'email est invalide',
                    ]),
                    new Length(['max' => 180])
                ],
            ])

            ->add('subject', EntityType::class, [
                'class' => SubjectEmail::class,
                'choice_label' => 'label',
                'label' => 'Objet',
                'placeholder' => 'Sélectionnez un objet',
                'attr' => [
                    'class' => 'form-control form-select',
                ],
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'L\'objet est obligatoire',
                    ]),
                ],
            ])

            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'attr' => [
                    'minlength' => 10,
                    'maxlength' => 1000,
                    'placeholder' => 'Votre message',
                    'class' => 'form-control textarea-message',
                    'required' => true,
                ],
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le message est obligatoire',
                    ]),
                    new Length([
                        'min' => 10,
                        'max' => 1000,
                        'minMessage' => 'Le message doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le message ne doit pas contenir plus de {{ limit }} caractères',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactMessage::class,
        ]);
    }
}
