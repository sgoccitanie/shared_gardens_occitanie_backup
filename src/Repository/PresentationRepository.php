<?php

namespace App\Repository;

use App\Entity\Presentation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PresentationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Presentation::class);
    }

    // Méthode pour récupérer la présentation (il n'y en a qu'une)
    public function findPresentation(): ?Presentation
    {
        return $this->findOneBy([], ['id' => 'ASC']);
    }
}
