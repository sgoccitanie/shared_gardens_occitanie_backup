<?php

namespace App\Form;

use App\Entity\Association;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Validator\Constraints\Blank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Constraints\Unique;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => [
                    'maxlength' => 180,
                ],
                'constraints' => [
                    new Unique([
                        'message' => 'Cet email existe déjà dans la BDD.',
                    ]),
                    new Blank([
                        'message' => 'L\'email doit être renseigné.'
                    ]),
                ],
            ])

            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter les conditions d\'utilisation.',
                    ]),
                ],
            ])

            ->add('firstname', TextType::class, [
                'attr' => [
                    'class' => 'register-input',
                    'minlength' => 1,
                    'maxlength' => 50,
                ]
            ])

            ->add('lastname', TextType::class, [
                'attr' => [
                    'class' => 'register-input',
                    'minlength' => 1,
                    'maxlength' => 50,
                ]
            ])

            ->add('login', TextType::class, [
                'attr' => ['class' => 'register-input'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez entrer un nom d\'utilisateur',
                    ]),
                ],
            ])

            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'label' => 'Mot de passe',
                'attr' => ['autocomplete' => 'off', 'aria-label' => 'password', 'aria-describedby' => 'passwordHelp'],
                'required' => true,
                'first_options'  => [
                    'label' => 'Mot de passe',
                    'attr' => ['class' => 'form-control password-field']
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr' => ['class' => 'form-control']
                ],
                'constraints' => [
                    new Length([
                        'min' => 12,
                        'minMessage' => 'Le mot de passe doit faire au moins {{ limit }} caractères',
                        'max' => 4096, // Protection contre les attaques par déni de service (ex: un mot de passe très long pourrait provoquer une erreur 500)
                    ]),
                    new PasswordStrength([
                        'minScore' => PasswordStrength::STRENGTH_MEDIUM,
                        'message' => 'Ce mot de passe est trop faible. Utilisez une combinaison de lettres, chiffres et symboles.',
                    ]),
                    //Contrainte Symfony qui utilise l'API HaveIBeenPwned (haveibeenpwned.com), un service gratuit créé par le chercheur en sécurité Troy Hunt, qui référence des milliards de mots de passe issus de fuites de données réelles.
                    new NotCompromisedPassword([
                        'message' => 'Ce mot de passe a été compromis dans une fuite de données. Choisissez-en un autre.',
                    ]),
                ],
            ])

            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Éditeur' => 'ROLE_EDITOR',
                    'Administrateur' => 'ROLE_ADMIN'
                ],
                'expanded' => false, // Menu déroulant
                'multiple' => true, // false = une seule sélection
                'label' => 'Rôles sur le site',
                'required' => true,
                'placeholder' => 'Sélectionnez un rôle',
            ])

            ->add('user_asso', EntityType::class, [
                'class' => Association::class,
                'label' => 'A quel réseau appartient l\'utilisateur ?',
                'attr' => ['class' => 'register-input'],
                'choice_label' => 'name',
                'placeholder' => 'Sélectionnez une association',
                'multiple' => false,
                'expanded' => false,
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'register_intention',
        ]);
    }
}
