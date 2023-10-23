<?php

namespace App\Repository;

use App\Entity\Movie;
use App\Model\Movie\MovieListCriteria;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Movie>
 *
 * @method Movie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Movie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Movie[]    findAll()
 * @method Movie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Movie::class);
    }

    public function add(Movie $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Movie $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
    
    /**
     * @return mixed[]
     */
    public function findByCriteria(MovieListCriteria $criteria): array
    {
        $queryBuilder = $this->createQueryBuilder('m')->orderBy('m.' . $criteria->getOrderByField(), $criteria->getSortOrder());
        if(!empty($criteria->getNameCriteria())) {
            $queryBuilder
                ->andWhere('m.name LIKE :name')
                ->setParameter('name', '%' . $criteria->getNameCriteria() . '%');
        }

        if(!empty($criteria->getGenreCriteria())) {
            $queryBuilder
                ->andWhere('m.genre = :genre')
                ->setParameter('genre', $criteria->getGenreCriteria());
        }

        $countQueryBuilder = clone $queryBuilder;
        $totals = $countQueryBuilder->select('COUNT(m.id)')->getQuery()->getSingleScalarResult();

        $queryBuilder->setMaxResults($criteria->getItemsPerPage());
        $queryBuilder->setFirstResult(($criteria->getPage() -1) * $criteria->getItemsPerPage());

        $results = $queryBuilder->getQuery()->getResult();

        return [$results, $totals];
    }

//    /**
//     * @return Movie[] Returns an array of Movie objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Movie
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
