<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Article|null find($id, $lockMode = null, $lockVersion = null)
 * @method Article|null findOneBy(array $criteria, array $orderBy = null)
 * @method Article[]    findAll()
 * @method Article[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function findAllWithCategoriesTagsAuthor(): array
    {
        $qb = $this->createQueryBuilder('a')
            ->innerJoin('a.category', 'c' )
            ->addSelect('c')
            ->leftJoin('a.tags', 't' )
            ->addSelect('t')
            ->leftJoin('a.author', 'author' )
            ->addSelect('author')
            ->getQuery();

        return $qb->execute();
    }

    /**
      * @return Article[] Returns an array of Article objects
      */

    public function searchByInput($input)
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.category', 'c' )
            ->addSelect('c')
            ->leftJoin('a.tags', 't' )
            ->addSelect('t')
            ->leftJoin('a.author', 'author' )
            ->addSelect('author')
            ->Where('a.title LIKE :input')
            ->setParameter('input', "%$input%")
            ->orderBy('a.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function random() :array
    {
        $connection = $this->getEntityManager()->getConnection();

        $sql = '
        SELECT * FROM article a
        ORDER BY RAND()
        LIMIT 1
        ';
        $stmt = $connection->prepare($sql);
        $stmt->execute();

        // returns an array of arrays (i.e. a raw data set)
        return $stmt->fetch();
    }
}
