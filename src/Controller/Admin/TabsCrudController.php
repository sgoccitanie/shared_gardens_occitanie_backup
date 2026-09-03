<?php

namespace App\Controller\Admin;

use App\Entity\Tabs;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Controller\Admin\Traits\EasyAdminAssetsTrait;
use App\Controller\Admin\Traits\EasyAdminActionsTrait;
use App\Entity\Posts;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

#[IsGranted('ROLE_EDITOR')]
class TabsCrudController extends AbstractCrudController
{
    use EasyAdminAssetsTrait;
    use EasyAdminActionsTrait;

    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public static function getEntityFqcn(): string
    {
        return Tabs::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Page')
            ->setEntityLabelInPlural('Pages')            
            ->setDefaultSort(['id' => 'DESC'])
            ->setPageTitle('index', 'Listes des %entity_label_plural%')
            ->setPageTitle('detail', fn(Tabs $tab) => (string) $tab)
            ->setPageTitle('edit', fn(Tabs $tab) => sprintf('Edition de la page "<b>%s</b>"', $tab->getLabel()))
            ->setFormThemes(['@EasyAdmin/crud/form_theme.html.twig', 'admin/posts/form.html.twig'])
        ;
    }
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        try {
            parent::persistEntity($entityManager, $entityInstance);
            $this->addFlash('success', 'La page "' . $entityInstance->getLabel() . '" a été créée avec succès.');
        } catch (\Exception $e) {
            $this->logger->error('Erreur création page : ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            $this->addFlash('danger', "La page n'a pas pu être enregistrée. Réessayez ou contactez l'administrateur.");
        }
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        try {
            /** @var Tabs $entityInstance */

            // Récupérer l'état actuel en base (avant modification)
            $originalTab = $entityManager->getUnitOfWork()->getOriginalEntityData($entityInstance);

            // Récupérer les posts après modification dans le formulaire.
            // La relation Posts-Tabs est ManyToMany et est portée par Posts.
            $postsInDb = $entityManager->getRepository(Posts::class)->createQueryBuilder('post')
                ->innerJoin('post.tabs', 'tab')
                ->andWhere('tab = :tab')
                ->setParameter('tab', $entityInstance)
                ->getQuery()
                ->getResult();
            $postsInForm = $entityInstance->getTabsPosts()->toArray();

            // Détacher les posts qui étaient en base mais plus dans le formulaire
            foreach ($postsInDb as $post) {
                if (!in_array($post, $postsInForm, true)) {
                    $post->removeTab($entityInstance);
                    $entityManager->persist($post);
                }
            }

            // Attacher les posts nouveaux
            foreach ($postsInForm as $post) {
                $post->addTab($entityInstance);
                $entityManager->persist($post);
            }
            // Assure l'unicité du slug
            $this->ensureUniqueSlug($entityManager, $entityInstance);

            parent::updateEntity($entityManager, $entityInstance);

            $this->addFlash(
                'success',
                'La page a été modifiée avec succès.'
            );
        } catch (\Exception $e) {
            $this->logger->error('Erreur modification page : ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            $this->addFlash('danger', "Les modifications de la page n'ont pas pu être enregistrées. Réessayez ou contactez l'administrateur.");
        }
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $this->configureCommonAssets($assets);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // Modifier "Enregistrer et continuer d'éditer"
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE, function (Action $action) {
                return $action->setLabel('Enregistrer et rester');
            })
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->update(Crud::PAGE_EDIT, Action::INDEX, function (Action $action) {
                return $action
                    ->setLabel('Retour aux pages')
                    ->setIcon('fa fa-arrow-left');
            })
            ->update(Crud::PAGE_NEW, Action::INDEX, function (Action $action) {
                return $action
                    ->setLabel('Retour aux pages')
                    ->setIcon('fa fa-arrow-left');
            });
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('label', 'Titre de la page'),
            SlugField::new('slug')->setTargetFieldName(['label'])->setFormTypeOption('attr', ['readonly' => true])->setUnlockConfirmationMessage(
                'Il est recommandé d\'utiliser les slugs automatiques, mais vous pouvez les personnaliser'
            ),
            AssociationField::new('categories')->setLabel('Catégorie associée'),
            AssociationField::new('tabs_posts', 'Article(s) associé(s)')
            ->setFormTypeOption('by_reference', false)
            ->setFormTypeOption('multiple', true)
            ->setFormTypeOption('choice_label', 'title')
            ->setHelp('Un article peut être rattaché à plusieurs pages'),
        ];
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Tabs $entityInstance */
        try {
            // Détacher tous les posts liés à cette tab (ils restent en BDD)
            foreach ($entityInstance->getTabsPosts() as $post) {
                $post->removeTab($entityInstance);
                $entityManager->persist($post);
            }
            // Assure l'unicité du slug
            $this->ensureUniqueSlug($entityManager, $entityInstance);

            parent::deleteEntity($entityManager, $entityInstance);
            $this->addFlash(
                'success',
                'La page a été supprimée avec succès.'
            );
        } catch (\Exception $e) {
            $this->logger->error('Erreur suppression page : ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            $this->addFlash('danger', "La page n'a pas pu être supprimée. Elle est peut-être liée à d'autres contenus.");
        }
    }

    private function ensureUniqueSlug(EntityManagerInterface $entityManager, Tabs $tab): void
    {
        $baseSlug = $tab->getSlug();
        if (empty($baseSlug)) {
            return;
        }

        $slug = $baseSlug;
        $counter = 1;

        while (true) {
            $existing = $entityManager->getRepository(Tabs::class)->findOneBy(['slug' => $slug]);

            // Si aucun autre tab avec ce slug (ou c'est le tab actuel), on garde
            if ($existing === null || $existing->getId() === $tab->getId()) {
                $tab->setSlug($slug);
                return;
            }

            // Sinon, ajoute un suffixe
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
    }
}
