<?php

namespace App\Repository;

use App\Entity\Conseil;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository dédié à l’entité Conseil.
 *
 * Ce repository gère :
 * - la récupération paginée de tous les conseils
 * - la récupération paginée des conseils filtrés par mois
 * - le comptage total des conseils (global ou filtré)
 *
 * Note importante :
 * Doctrine ORM ne supportant pas JSON_CONTAINS() avec MariaDB,
 * les méthodes filtrant par mois utilisent DBAL pour récupérer les IDs,
 * puis l’ORM pour charger les entités correspondantes.
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
     * Méthode qui récupère une liste paginée de tous les conseils.
     *
     * Utilise exclusivement Doctrine ORM.
     *
     * @param int $page  Numéro de page (>=1)
     * @param int $limit Nombre d’éléments par page
     *
     * @return Conseil[] Liste des conseils pour la page demandée
     */
    public function findAllPaginated(int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('c')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Méthode qui retourne le nombre total de conseils en base.
     *
     * Utilisé pour construire les métadonnées de pagination.
     *
     * @return int Nombre total de conseils
     */
    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    /**
     * Méthode qui récupère une liste paginée de conseils pour un mois donné.
     *
     * attention! Doctrine ORM ne supportant pas JSON_CONTAINS() avec MariaDB,
     * cette méthode utilise DBAL pour récupérer les IDs filtrés et paginés, puis l'ORM pour charger les entités.
     *
     * Étapes :
     * 1) DBAL → SELECT id FROM conseil WHERE JSON_CONTAINS(mois, :mois)
     * 2) ORM  → SELECT * FROM conseil WHERE id IN (:ids)
     * 
     * @param int $mois  Mois (1-12)
     * @param int $page  Numéro de page (>=1)
     * @param int $limit Nombre d’éléments par page
     *
     * @return Conseil[] Liste des conseils pour la page demandée du mois donné
     */
    public function findByMonthPaginated(int $mois, int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;

        // JSON_CONTAINS est une fonction SQL native (MariaDB/MySQL) non supportée en DQL.
        // On passe par la connexion DBAL pour récupérer les IDs, puis on charge les entités via l'ORM.
        //1)charger les IDs des conseils correspondant au mois filtrés et paginés
        $ids = $this->getEntityManager()
            ->getConnection()
            ->executeQuery(
                'SELECT id FROM conseil WHERE JSON_CONTAINS(mois, :mois) LIMIT :limit OFFSET :offset',
                ['mois' => json_encode($mois), 'limit' => $limit, 'offset' => $offset],
                ['limit' => ParameterType::INTEGER, 'offset' => ParameterType::INTEGER]
            )
            ->fetchFirstColumn();//fetchFirstColumn() retourne un tableau d'IDs (ex: [1, 5, 9])

        if (empty($ids)) {
            return [];
        }

        //2) Charger les entités via l'ORM en utilisant les IDs récupérés
        return $this->createQueryBuilder('c')
            ->andWhere('c.id IN (:ids)')
            ->setParameter('ids', $ids)
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
    public function countByMonth(int $mois): int
    {
        return (int) $this->getEntityManager()
            ->getConnection()
            ->executeQuery(
                'SELECT COUNT(*) FROM conseil WHERE JSON_CONTAINS(mois, :mois)',
                ['mois' => json_encode($mois)]
            )
            ->fetchOne();
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
