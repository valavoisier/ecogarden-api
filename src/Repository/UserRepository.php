<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * Repository dédié à l’entité User.
 *
 * Ce repository gère :
 * - la mise à jour automatique du hash de mot de passe (PasswordUpgraderInterface)
 * - la récupération paginée des utilisateurs
 * - le comptage total des utilisateurs
 *
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Met à jour (rehash) le mot de passe d’un utilisateur lorsque l’algorithme évolue.
     *
     * Cette méthode est appelée automatiquement par Symfony lorsque :
     * - l’algorithme de hash change,
     * - le coût de calcul augmente,
     * - ou lorsqu’un rehash est jugé nécessaire.
     *
     * @throws UnsupportedUserException Si l’objet fourni n’est pas une instance de User
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        // Vérifie que l’utilisateur est bien une instance de User.
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }
        // Mise à jour du hash du mot de passe.
        $user->setPassword($newHashedPassword);
        // Persistance et sauvegarde en base de données.
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
    
    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
