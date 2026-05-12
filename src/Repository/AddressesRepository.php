<?php

namespace App\Repository;

use App\Entity\Addresses;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AddressesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Addresses::class);
    }

    /**
     * Récupèrer les adresses avec des coordonnées valides et une ville existante
     */
    public function findValidAddresses(): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.city', 'c')
            ->addSelect('c') // Charge l'entité City
            ->where('a.latitude IS NOT NULL')
            ->andWhere('a.longitude IS NOT NULL')
            ->andWhere('a.latitude BETWEEN 40 AND 52') // Plage réaliste pour la France
            ->andWhere('a.longitude BETWEEN -5 AND 10') // Plage réaliste pour la France
            ->getQuery()
            ->getResult();
    }
}
