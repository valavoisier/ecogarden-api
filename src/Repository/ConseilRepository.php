<?php

namespace App\Repository;

use App\Entity\Conseil;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository dédié à l’entité Conseil.
 *
 * Ce repository gère :
 * - la récupération paginée de tous les conseils
 * - la récupération paginée des conseils filtrés par mois
 * - le comptage total des conseils (global ou filtré)
 *
 *  * @extends ServiceEntityRepository<Conseil>
 */
class ConseilRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conseil::class);
    }

    /**
     * Méthode qui récupère une liste paginée de conseils pour un mois donné.
     *
     
     * @param int $mois  Mois (1-12)
     * @param int $page  Numéro de page (>=1)
     * @param int $limit Nombre d’éléments par page
     *
     * @return Conseil[] Liste des conseils pour la page demandée du mois donné
     */
    public function findByMonthPaginated(int $moisId, int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;

         return $this->createQueryBuilder('c')
        ->join('c.mois', 'm')
        ->andWhere('m.id = :moisId')
        ->setParameter('moisId', $moisId)
        ->setFirstResult($offset)
        ->setMaxResults($limit)
        ->orderBy('c.id', 'ASC')
        ->getQuery()
        ->getResult();
    }

    /**
     * Méthode qui compte le nombre total de conseils associés à un mois donné.
     *
     * Utilisé pour construire les métadonnées de pagination
     * dans l’endpoint GET /api/conseil/{mois}.
     *
     * @param int $mois Mois recherché (1–12)
     *
     * @return int Nombre total de conseils pour ce mois
     */
    public function countByMonth(int $moisId): int
    {
        return (int) $this->createQueryBuilder('c')
        ->select('COUNT(DISTINCT c.id)')
        ->join('c.mois', 'm')
        ->andWhere('m.id = :moisId')
        ->setParameter('moisId', $moisId)
        ->getQuery()
        ->getSingleScalarResult();
    }


    //    /**
    //     * @return Conseil[] Returns an array of Conseil objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Conseil
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
