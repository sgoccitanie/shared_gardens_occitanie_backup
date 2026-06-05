<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Controller\Admin\Traits\ReadOnlyTraits;
use DateTimeImmutable;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use App\Controller\Admin\Traits\EasyAdminAssetsTrait;
use App\Controller\Admin\Traits\EasyAdminActionsTrait;

#[IsGranted('ROLE_ADMIN')]
class UserCrudController extends AbstractCrudController
{
    use EasyAdminAssetsTrait;
    use EasyAdminActionsTrait;

    public function __construct(
        private readonly UserPasswordHasherInterface $userPasswordHasher,

        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminUrlGenerator $adminUrlGenerator
    ) {}

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $this->configureCommonAssets($assets);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud

            // the labels used to refer to this entity in titles, buttons, etc.
            ->setEntityLabelInSingular('Membre')
            ->setEntityLabelInPlural('Membres')
            ->setPageTitle('index', 'Listes des %entity_label_plural%')
            ->setPageTitle('detail', fn(User $user) => (string) $user)
            ->setPageTitle('edit', fn(User $user) => sprintf('Edition de "<b>%s</b>', $user->getFirstName() . ' ' . $user->getLastName() . '"'))
            ->setEntityPermission('ROLE_ADMIN')
            ->setFormThemes(['@EasyAdmin/crud/form_theme.html.twig', 'admin/posts/form.html.twig'])
        ;
    }

    // Ghoster/réactiver un user, sauf le rôle choisit par l'admin
    public function configureActions(Actions $actions): Actions
    {
        $ghostAction = Action::new('ghost', 'Ghoster', 'fas fa-ghost')
            ->linkToCrudAction('ghostUser')
            ->setCssClass('btn btn-secondary');

        $unghostAction = Action::new('unghost', 'Réactiver', 'fas fa-undo')
            ->linkToCrudAction('unghostUser')
            ->setCssClass('btn btn-success');

        return $actions
            ->add(Crud::PAGE_INDEX, $ghostAction)
            ->add(Crud::PAGE_INDEX, $unghostAction)
            ->add(Crud::PAGE_DETAIL, $ghostAction)
            ->add(Crud::PAGE_DETAIL, $unghostAction)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->disable(Action::DELETE);
    }

    // Ghoster
    public function ghostUser(AdminContext $context): Response
    {
        $user = $context->getEntity()->getInstance();

        if (!$user instanceof User) {
            $this->addFlash('error', 'Une erreur est survenue.');
            return $this->redirect($this->adminUrlGenerator->setRoute('admin')->generateUrl());
        }

        // Supprimer tous les rôles pour un membre ghosté
        $user->setRoles([]);
        $user->setIsGhosted(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->addFlash('success', 'L\'utilisateur a été ghosté et n\'a plus aucun rôle.');
        return $this->redirect($this->adminUrlGenerator->setRoute('admin')->setController(UserCrudController::class)->generateUrl());
    }

    // Réactiver un membre ghosté
    public function unghostUser(AdminContext $context): Response
    {
        $user = $context->getEntity()->getInstance();

        if (!$user instanceof User) {
            $this->addFlash('error', 'Une erreur est survenue.');
            return $this->redirect($this->adminUrlGenerator->setRoute('admin')->generateUrl());
        }

        // Désactiver le ghosting mais c'est l'admin qui choisit un rôle
        $user->setIsGhosted(false);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->addFlash('success', 'L\'utilisateur a été réactivé. Veuillez lui réattribuer ses rôles si nécessaire.');
        return $this->redirect($this->adminUrlGenerator->setRoute('admin')->setController(UserCrudController::class)->generateUrl());
    }

    public function configureFields(string $pageName): iterable
    {
        $passwordMeter = '  <div class="password-meter">
                                <div class="meter-section rounded me-2 weak"></div>
                                <div class="meter-section rounded me-2 medium"></div>
                                <div class="meter-section rounded me-2 strong"></div>
                                <div class="meter-section rounded very-strong"></div>
                            </div>
                            <div id="passwordHelp" class="form-text text-muted">
                                Utilisez 8 caractères ou plus avec une combinaison de lettres, de chiffres et de symboles.
                            </div>';
        $fields = [
            FormField::addPanel('Informations générales')
                ->setIcon('fa fa-user')->addCssClass('required'),
            IdField::new('id')->hideOnForm(),
            Field::new('login'),
            Field::new('firstname')->setLabel("Prénom"),
            Field::new('lastname')->setLabel('Nom'),

            FormField::addPanel('Informations de contact')
                ->setIcon('fa fa-mobile')->addCssClass('required'),
            EmailField::new('email'),

            // User ghosté
            BooleanField::new('isGhosted', 'Ghosté ?')
                ->renderAsSwitch(false)
                ->onlyOnIndex(),
        ];

        // Seuls l'administrateur peut modifier les rôles
        // Si l'utilisateur connecté est strictement admin
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $roles = ['ROLE_EDITOR', 'ROLE_ADMIN'];
            // Ajouter le champ 'roles'
            $fields[] = ChoiceField::new('roles')
                ->setChoices(array_combine($roles, $roles))
                ->allowMultipleChoices()
                ->renderExpanded()
                ->setLabel('Rôles');
        }

        if ($pageName === Crud::PAGE_NEW) {
            $password = TextField::new('password')
                ->setFormType(RepeatedType::class) // Confirmer le mot de passe
                ->setFormTypeOptions([
                    'type' => PasswordType::class,
                    'first_options' => ['label' => 'Mot de passe'],
                    'second_options' => ['label' => 'Répéter le mot de passe'],
                    'mapped' => true,
                ])
                ->setRequired($pageName === Crud::PAGE_NEW)
                ->onlyWhenCreating();
        } else {
            $password = TextField::new('password')->setLabel("Mot de passe")->setFormTypeOptions(['mapped' => false, 'required' => false])->onlyWhenCreating();
        }

        $fields[] = $password;
        return $fields;
    }

    use ReadOnlyTraits;
}
