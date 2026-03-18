<?php

namespace App\Repository;

use App\Entity\Conseil;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository dédié à l’entité Conseil.
 *
 * Ce repository gère :
 * - la récupération paginée des conseils filtrés par mois
 * - le comptage total des conseils pour un mois donné (utile pour la pagination)
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
     * Méthode qui récupère une liste paginée de conseils associés à un mois donné.
     *
     * 1) On récupère uniquement les IDs des conseils correspondant au mois demandé,
     *    avec pagination. Cela évite les problèmes liés au fetch join + pagination
     *    (Doctrine duplique les lignes et fausse les limites).
     *
     * 2) Une fois les IDs obtenus, on recharge les entités complètes avec leurs relations
     *    (ici les mois) via un second QueryBuilder.
     *
     * @param int $moisNumero  Numéro du mois (1-12)
     * @param int $page        Numéro de page (>=1)
     * @param int $limit       Nombre d’éléments par page
     *
     * @return Conseil[] Liste paginée des conseils du mois demandé
     */
    public function findByMonthPaginated(int $moisNumero, int $page = 1, int $limit = 10): array
    {
        // Calcul de l’offset pour la pagination.
        $offset = ($page - 1) * $limit;
        
        // ───────────────────────────────────────────────────────────────
        // 1) Première requête : récupérer uniquement les IDs paginés
        // ───────────────────────────────────────────────────────────────
        //
        // On ne charge pas encore les entités complètes pour éviter les doublons
        // causés par les fetch join lorsqu’ils sont combinés avec LIMIT/OFFSET.
        //
        // Ligne par ligne :
        // - createQueryBuilder('c') : construit une requête sur Conseil (alias c)
        // - select('c.id') : on ne récupère que les IDs
        // - join('c.mois', 'm') : jointure pour filtrer par mois
        // - andWhere('m.numero = :numero') : filtre sur le mois demandé
        // - setParameter('numero', $moisNumero) : paramètre sécurisé
        // - setFirstResult($offset) : applique l’offset
        // - setMaxResults($limit) : limite le nombre de résultats
        // - orderBy('c.id', 'ASC') : tri stable pour la pagination
        // - getSingleColumnResult() : retourne un tableau simple d’IDs
        $ids = $this->createQueryBuilder('c')
            ->select('c.id')
            ->join('c.mois', 'm')
            ->andWhere('m.numero = :numero')
            ->setParameter('numero', $moisNumero)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        // Si aucun conseil ne correspond → retourne un tableau vide.
        if (empty($ids)) {
            return [];
        }
        // ───────────────────────────────────────────────────────────────
        // 2) Deuxième requête : recharger les entités complètes
        // ───────────────────────────────────────────────────────────────
        //
        // Cette fois, on charge les conseils + leurs relations (mois).
        // - leftJoin : charge tous les mois associés
        // - addSelect('m') : inclut les données de Mois dans le SELECT
        // - IN (:ids) : recharge uniquement les conseils trouvés à l’étape 1
        return $this->createQueryBuilder('c')
            ->leftJoin('c.mois', 'm')
            ->addSelect('m')
            ->andWhere('c.id IN (:ids)')
            ->setParameter('ids', $ids)
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
    public function countByMonth(int $moisNumero): int
    {
        // Construction de la requête :
        // - COUNT(DISTINCT c.id) : évite les doublons si un conseil est lié à plusieurs mois
        // - join('c.mois', 'm') : jointure pour filtrer par mois
        // - andWhere('m.numero = :numero') : filtre sur le mois demandé
        // - setParameter() : paramètre sécurisé (requête préparée)
        // - getSingleScalarResult() : retourne un entier (le COUNT)
        return (int) $this->createQueryBuilder('c')
        ->select('COUNT(DISTINCT c.id)')
        ->join('c.mois', 'm')
        ->andWhere('m.numero = :numero')
        ->setParameter('numero', $moisNumero)
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
