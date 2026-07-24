<?php

namespace App\Repository;

use App\Entity\Categories;
use App\Entity\Tabs;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tabs>
 */
class TabsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tabs::class);
    }

    /**
     * @return Tabs[]
     */
    public function findByCategory(Categories $category): array
    {
        return $this->createQueryBuilder('t')
        ->innerJoin('t.categories', 'c')
        ->leftJoin('t.tabs_posts', 'p')
        ->addSelect('p')
        ->where('c = :category')
        ->setParameter('category', $category)
        ->getQuery()
        ->getResult();
    }

    //    /**
    //     * @return Tabs[] Returns an array of Tabs objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Tabs
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
