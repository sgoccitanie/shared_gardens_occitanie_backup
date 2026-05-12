<?php
// Cette commande sert à remplacer le mot de passe existant avec un mot de passe haché (mode env)
// Étapes: 
// 1) vider le cache
// 2) Éxécuter la commande bin/console dans le terminal pour générer le nouveau mot de passe haché
// 3) Vérifier/Tester

namespace App\Command;

use App\Entity\User;
use App\Service\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-user-password',
    description: 'Met à jour le mot de passe d\'un utilisateur.',
)]
class UpdateUserPasswordCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email de l\'utilisateur')
            ->addArgument('password', InputArgument::REQUIRED, 'Nouveau mot de passe');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');

        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error('Utilisateur non trouvé.');
            return Command::FAILURE;
        }

        $hashedPassword = Utils::hash($password);
        $user->setPassword($hashedPassword);

        $this->entityManager->flush();

        $io->success('Mot de passe mis à jour avec succès.');

        return Command::SUCCESS;
    }
}