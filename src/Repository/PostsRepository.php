<?php

namespace App\Repository;

use App\Entity\Posts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Posts>
 */
class PostsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Posts::class);
    }
 
    public function findCategory(int $postId): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.tabs', 't')
            ->innerJoin('t.categories', 'c')
            ->addSelect('t', 'c')
            ->where('p.id = :postId')
            ->setParameter('postId', $postId)
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(int $categoryId, string $order = 'DESC'): array
    {
       $order = in_array(strtoupper($order), ['ASC', 'DESC'], true) ? strtoupper($order) : 'DESC';

        return $this->createQueryBuilder('p')
            ->select('DISTINCT p')
            ->innerJoin('p.tabs', 't')
            ->innerJoin('t.categories', 'c')
            ->where('c.id = :categoryId')
            ->andWhere('p.status = 1')
            ->setParameter('categoryId', $categoryId)
            ->orderBy('p.posted_at', $order)
            ->getQuery()
            ->getResult();
    }

    public function findBySlug(int $slug, string $order = 'DESC'): array
    {
       $order = in_array(strtoupper($order), ['ASC', 'DESC'], true) ? strtoupper($order) : 'DESC';

        return $this->createQueryBuilder('p')
            ->select('DISTINCT p')
            ->innerJoin('p.tabs', 't')
            ->andWhere('p.status = 1')
            ->setParameter('slug', $slug)
            ->orderBy('p.posted_at', $order)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Posts[] Returns an array of Posts objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Posts
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
