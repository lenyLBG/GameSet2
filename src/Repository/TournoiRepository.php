<?php

namespace App\Repository;

use App\Entity\Tournoi;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tournoi>
 */
class TournoiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tournoi::class);
    }

    public function save(Tournoi $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Search tournois by a query string over name, sport and format.
     * Returns all tournois if $q is empty or null.
     *
     * @return Tournoi[]
     */
    public function findBySearch(?string $q): array
    {
        $qb = $this->createQueryBuilder('t')
            ->orderBy('t.dateDebut', 'DESC');

        if ($q !== null && trim($q) !== '') {
            $qb->andWhere('t.nom LIKE :q OR t.sport LIKE :q OR t.format LIKE :q')
               ->setParameter('q', '%'.trim($q).'%');
        }

        return $qb->getQuery()->getResult();
    }
}
