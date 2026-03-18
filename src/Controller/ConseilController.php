<?php

namespace App\Controller;

use App\Entity\Conseil;
use App\Repository\ConseilRepository;
use App\Repository\MoisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException; 
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class ConseilController extends AbstractController
{    
    /** 
     *  Cette méthode permet de récupérer les conseils d’un mois donné (1–12)
     * 
     * Méthode : GET
     * URL     : /api/conseil/{mois}  
     * Rôle    : ROLE_USER
     *
     * Paramètres :
     * - mois : entier entre 1 et 12     
     * - page  : numéro de page
     * - limit : nombre d’éléments par page
     *
     * Exemple :
     * GET /api/conseil/5
     * 
     * Exemple de réponse :
     * {
     *   "data": [...],
     *   "pagination": { "page": 2, "limit": 10, "total": 18, "pages": 2 }
     * }
     *
     * Codes de réponse :
     * - 200 : Succès
     * - 400 : Paramètre invalide
     * 
     * @param int $mois
     * @return JsonResponse 
     */ 
    //Limite valeurs acceptées à 1-12. Sinon 404 automatique avant entrée dans controller
    #[Route('/api/conseil/{mois}', name: 'conseil_by_month', methods: ['GET'], requirements: ['mois' => '[1-9]|1[0-2]'])] 
    public function getConseilsByMonth(int $mois, ConseilRepository $conseilRepository, Request $request, TagAwareCacheInterface $cachePool): JsonResponse 
    { 
        //Récupération sécurisée de la pagination (min = 1)
        $page  = max(1, $request->query->getInt('page', 1));
        $limit = max(1, $request->query->getInt('limit', 10));

        // Clé de cache unique pour chaque combinaison mois/page/limit
        //évite problème de cache non expiré au changement de mois ou pagination
        $idCache = "getConseilsByMonth-" . $mois . "-" . $page . "-" . $limit;//construction clé de cache unique 

        // Lecture du cache : si la clé existe, renvoie la valeur.
        // Sinon, exécute le callback, stocke le résultat et le renvoie.
        $result  = $cachePool->get($idCache, function (ItemInterface $item) use ($conseilRepository, $mois, $page, $limit) {
            // Tags permettant d’invalider tout le cache ou seulement celui d’un mois
            $item->tag(['conseilsCache', "conseilsCache-month-{$mois}"]);
            $item->expiresAfter(86400);// Durée de vie du cache : 24h

            // Récupération paginée des conseils du mois
            $conseils = $conseilRepository->findByMonthPaginated($mois, $page, $limit);
            
            // Conversion en tableaux scalaires :
            // - évite les proxies Doctrine non sérialisables
            // - garantit un cache stable et compatible JSON
            return [
                'conseils' => array_map(fn($c) => [
                    'id'      => $c->getId(),
                    'contenu' => $c->getContenu(),
                    // Transforme la Collection Doctrine en tableau simple de numéros de mois pour éviter les problèmes de sérialisation dans le cache
                    'mois'    => $c->getMois()->map(fn($m) => $m->getNumero())->toArray(),
                ], $conseils),
                // Nombre total de conseils pour la pagination
                'total' => $conseilRepository->countByMonth($mois),
            ];
        });

        // Construction de la réponse JSON avec pagination complète
        return $this->json([
            'data'       => $result['conseils'],
            'pagination' => [
                'page'  => $page,
                'limit' => $limit,
                'total' => $result['total'],
                // ceil() arrondit au nombre entier supérieur pour obtenir le nombre total de pages
                // Exemple : 11 éléments avec une limite de 10 → 2 pages
                'pages' => (int) ceil($result['total'] / $limit),
            ],
        ]);
    }

    /** 
     *  Cette méthode permet de récupérer les conseils du mois en cours
     * 
     * Méthode : GET
     * URL     : /api/conseil 
     * Rôle    : ROLE_USER
     *
     * Exemple :
     * GET /api/conseil
     *
     *  Exemple de réponse :
     * {
     *   "data": [
     *      {
     *        "id": 30,
     *        "contenu": "Taillez vos rosiers pour favoriser une belle floraison.",
     *        "mois": [
     *           2,
     *           3
     *        ]
     *      }...
     *   ],
     *   "pagination": {"page": 1, "limit": 10, "total": 6, "pages": 1}
     *   }
     * }
     *
     * Codes de réponse :
     * - 200 OK : Succès
     * @return JsonResponse 
     */ 
    #[Route('/api/conseil', name: 'conseil_current_month', methods: ['GET'])] 
    public function getConseilsCurrentMonth(ConseilRepository $conseilRepository, Request $request, TagAwareCacheInterface $cachePool): JsonResponse 
    { 
        $mois  = (int) date('n');//mois actuel (1-12)

        // Pagination sécurisée (min = 1)
        $page  = max(1, $request->query->getInt('page', 1));
        $limit = max(1, $request->query->getInt('limit', 10));
 
        // Clé de cache unique pour mois/page/limit
        //évite problème de cache non expiré au changement de mois ou pagination
        $idCache = "getConseilsCurrentMonth-" . $mois . "-" . $page . "-" . $limit; 

        // Lecture du cache : si la clé existe → renvoie la valeur.
        // Sinon → exécute le callback, stocke le résultat et le renvoie.
        $result  = $cachePool->get($idCache, function (ItemInterface $item) use ($conseilRepository, $mois, $page, $limit) {
            // Tags pour invalider tout le cache ou seulement celui d’un mois
            $item->tag(['conseilsCache', "conseilsCache-month-{$mois}"]);
            $item->expiresAfter(86400);//cache 24h

            // Récupération paginée des conseils du mois courant
            $conseils = $conseilRepository->findByMonthPaginated($mois, $page, $limit);
           
            // Conversion en tableaux scalaires :
            // - évite les proxies Doctrine non sérialisables
            // - garantit un cache stable et compatible JSON
            return [
                'conseils' => array_map(fn($c) => [
                    'id'      => $c->getId(),
                    'contenu' => $c->getContenu(),
                    // Collection Doctrine → tableau simple de numéros de mois pour éviter les problèmes de sérialisation dans le cache
                    'mois'    => $c->getMois()->map(fn($m) => $m->getNumero())->toArray(),
                ], $conseils),
                //nombre total de conseils pour le mois actuel pour pagination
                'total' => $conseilRepository->countByMonth($mois),
            ];
        });
        // Réponse JSON avec pagination complète
        return $this->json([
            'data'       => $result['conseils'],
            'pagination' => [
                'page'  => $page,
                'limit' => $limit,
                'total' => $result['total'],
                // ceil() arrondit au nombre entier supérieur pour obtenir le nombre total de pages
                // Exemple : 11 éléments avec une limite de 10 → 2 pages
                'pages' => (int) ceil($result['total'] / $limit),
            ],
        ]);
    }

    /** 
     *  Cette méthode permet de créer un nouveau conseil 
     * *
     * Méthode : POST  
     * URL     : /api/conseil  
     * Rôle    : ROLE_ADMIN
     *
     * Exemple de requête :
     * {
     *   "contenu": "Semez des radis dès le mois de mars.",
     *   "mois": [3, 4]
     * }
     *
     * Exemple de réponse :
     * {
     *   "id": 51,
     *   "contenu": "Semez des radis dès le mois de mars.",
     *   "mois": [3, 4]
     * }
     *
     * Codes de réponse :
     * - 201 : Conseil créé
     * - 400 : JSON invalide
     * - 422 : Erreurs de validation
     * 
     * @return JsonResponse
     */ 
    #[Route('/api/conseil', name: 'conseil_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un conseil')]//Admin sinon 403 Forbidden     
    public function createConseil( 
        Request $request, 
        EntityManagerInterface $entityManager, 
        ValidatorInterface $validator,
        MoisRepository $moisRepository,
        TagAwareCacheInterface $cachePool
        ): JsonResponse { 
        // Lecture et décodage du JSON envoyé par le client, en s'assurant que c'est un tableau associatif (true)
        $data = json_decode($request->getContent(), true); 
        if ($data === null) { 
            throw new BadRequestHttpException('JSON invalide.'); // error 400 si JSON mal formé ou non décodable
        } 
        // Création de l'entité Conseil et hydratation du contenu.
        // Le "?? ''" évite une erreur si le champ n'est pas fourni.
        $conseil = new Conseil(); 
        $conseil->setContenu($data['contenu'] ?? '');
        // Gestion de la relation ManyToMany avec Mois.
        // On vérifie que "mois" existe et que c'est bien un tableau.
        // Sinon, on utilise un tableau vide pour éviter toute erreur.
        $moisIds = isset($data['mois']) && is_array($data['mois']) ? $data['mois'] : []; 
        // Pour chaque numéro de mois envoyé (ex : 3, 4),
        // on récupère l'entité Mois correspondante via son champ "numero".
        // Si elle existe, on l'associe au Conseil via addMois(),
        // qui synchronise aussi le côté inverse de la relation ManyToMany.
        foreach ($moisIds as $moisId) {
            $mois = $moisRepository->findOneBy(['numero' => (int) $moisId]);
            if ($mois) {
                $conseil->addMois($mois);
            }
        }       
        // Validation de l'entité (vérification des contraintes définies dans l'entité Conseil)
        $errors = $validator->validate($conseil); 
        // Si des erreurs existent, on les transforme en tableau "champ => message"
        // puis on renvoie une erreur 422 (Unprocessable Entity).
        if (count($errors) > 0) { 
            $messages = []; //tableau associatif nom du champ => message
            foreach ($errors as $error) { 
                $messages[$error->getPropertyPath()] = $error->getMessage(); 
            } 
            throw new UnprocessableEntityHttpException(json_encode($messages));//422 Unprocessable Entity
        }

        // Persistance en base : persist() marque l'entité pour insertion,
        // flush() exécute réellement les requêtes SQL (conseil + table de jointure ManyToMany).
        $entityManager->persist($conseil); 
        $entityManager->flush();

        // Invalidation du cache : toute création modifie les listes de conseils,
        // donc on invalide les tags pour forcer un rafraîchissement propre.
        $cachePool->invalidateTags(['conseilsCache']);

        // Réponse JSON : on renvoie un tableau scalaire (jamais une entité Doctrine),
        // pour éviter les proxies et garantir une sérialisation propre.
        // La relation ManyToMany est convertie en tableau de numéros de mois.
        return $this->json([
            'id'      => $conseil->getId(),
            'contenu' => $conseil->getContenu(),
            'mois'    => $conseil->getMois()->map(fn($m) => $m->getNumero())->toArray(),
        ], Response::HTTP_CREATED);//201 Created
    }

    /**
     * Cette méthode permet de mettre à jour un conseil existant
     * *
     * Méthode : PUT  
     * URL     : /api/conseil/{id}  
     * Rôle    : ROLE_ADMIN
     *
     * Exemple de requête :
     * {
     *   "contenu": "Contenu mis à jour.",
     *   "mois": [4, 5]
     * }
     *
     * Exemple de réponse :
     * (aucun contenu, code 204)
     * 
     * Codes de réponse :
     * - 204 : Mise à jour réussie
     * - 400 : JSON invalide
     * - 404 : Conseil non trouvé
     * - 422 : Erreurs de validation
     *
     * @param int $id
     * @return JsonResponse
     */    
    #[Route('/api/conseil/{id}', name: 'conseil_update', methods: ['PUT'], requirements: ['id' => '\d+'])]//{id} est un entier
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour mettre à jour un conseil')]
    public function updateConseil(
        int $id,
        Request $request,
        ConseilRepository $conseilRepository,
        EntityManagerInterface $em,
        ValidatorInterface $validator,     
        MoisRepository $moisRepository,
        TagAwareCacheInterface $cachePool
    ): JsonResponse {
        // Vérification de l’existence du conseil.
        // Si l’ID ne correspond à aucun enregistrement → 404.
        $conseil = $conseilRepository->find($id);

        if (!$conseil) {
            throw new NotFoundHttpException('Conseil non trouvé.');// 404 Not Found
        }

        // Lecture et décodage du JSON envoyé par le client.
        // Le second paramètre (true) force un tableau associatif.
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            throw new BadRequestHttpException('JSON invalide.');// 400 Bad Request
        }

        // Mise à jour du contenu.
        // Si "contenu" n’est pas fourni, on conserve l’ancien.
        $conseil->setContenu($data['contenu'] ?? $conseil->getContenu());
        // Mise à jour des mois uniquement si le champ "mois" est présent dans le JSON.
        if (array_key_exists('mois', $data)) {
            // Le champ doit obligatoirement être un tableau. Sinon, on renvoie une erreur 400.
            if (!is_array($data['mois'])) {
                throw new BadRequestHttpException('Le champ mois doit être un tableau d\'entiers.');// 400 Bad Request
            }
            // Suppression de tous les mois existants.
            // removeMois() synchronise aussi le côté inverse de la relation ManyToMany (Mois::$conseils).
            foreach ($conseil->getMois()->toArray() as $ancienMois) {
                $conseil->removeMois($ancienMois);
            }
            // Ajout des nouveaux mois à partir du tableau de numéros envoyés dans le JSON.
            // $moisId représente un numéro de mois (ex : 3 pour mars).
            foreach ($data['mois'] as $moisId) {
                // Recherche de l’entité Mois correspondante au numéro (entier) envoyé.
                $mois = $moisRepository->findOneBy(['numero' => (int) $moisId]); 
                
                // Si le mois existe, on l’associe au Conseil.
                // addMois() met aussi à jour le côté inverse de la relation.
                if ($mois) {                    
                    $conseil->addMois($mois);
                }
            }
        }

        // Validation de l’entité après mise à jour
        $errors = $validator->validate($conseil);

        // Si des erreurs existent, on construit un tableau "champ => message"
        // puis on renvoie une erreur 422 (Unprocessable Entity).
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                // getPropertyPath() = nom du champ concerné
                // getMessage() = message d’erreur défini dans la contrainte
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
                throw new UnprocessableEntityHttpException(json_encode($messages));// 422 Unprocessable Entity
            }

        // Sauvegarde des modifications en base
        $em->flush();

        // Invalidation du cache après mise à jour
        $cachePool->invalidateTags(['conseilsCache']);

        // Réponse 204 : mise à jour réussie, aucun contenu retourné.
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);// 204 No Content
    }

    /**
     * Cette méthode permet de supprimer un conseil existant
     * *
     * Méthode : DELETE  
     * URL     : /api/conseil/{id}  
     * Rôle    : ROLE_ADMIN
     *
     * Exemple de réponse :
     * (aucun contenu, code 204)
     * 
     * Codes de réponse :
     * - 204 : Suppression réussie
     * - 404 : Conseil non trouvé
     *
     * @param int $id
     * @return JsonResponse
     */
    #[Route('/api/conseil/{id}', name: 'conseil_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]//{id} doit être un entier
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un conseil')]
    public function deleteConseil(
        int $id,
        ConseilRepository $conseilRepository,
        EntityManagerInterface $entityManager,
        TagAwareCacheInterface $cachePool
    ): JsonResponse {
        // Recherche du conseil à supprimer.
        // Si aucun conseil ne correspond à l’ID → erreur 404.
        $conseil = $conseilRepository->find($id);

        if (!$conseil) {
            throw new NotFoundHttpException('Conseil non trouvé.');//404 Not Found
        }
        // Suppression de l’entité Conseil.
        // remove() marque l’entité pour suppression dans l’unité de travail Doctrine.
        // La suppression réelle (DELETE SQL) sera exécutée au flush().
        $entityManager->remove($conseil);
        // Exécution des opérations en attente :
        // - suppression du conseil
        // - suppression des lignes dans la table de jointure ManyToMany
        $entityManager->flush();

        // Invalidation du cache après suppression
        // invalidateTags() supprime toutes les entrées portant le tag 'conseilsCache'.
        $cachePool->invalidateTags(['conseilsCache']);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);//204 No Content
    }

    /**
     * Vide manuellement le cache des conseils (administration).
     * Le cache est normalement invalidé automatiquement à chaque POST, PUT ou DELETE.
     * Cette route sert de mécanisme de secours (ex : modification directe en base).
     *
     * Méthode : DELETE
    * URL     : /api/conseil/cache
     * Rôle    : ROLE_ADMIN
     *
     * Codes de réponse :
     * - 204 : Cache invalidé avec succès (aucun contenu)
     *
     * @param TagAwareCacheInterface $cachePool
     * @return JsonResponse
     */
    #[Route('/api/conseil/cache', name: 'conseil_clear_cache', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour vider le cache')]
    public function clearCache(TagAwareCacheInterface $cachePool): JsonResponse
    {
        // Invalidation globale du cache lié aux conseils.
        // invalidateTags() supprime toutes les entrées portant le tag 'conseilsCache'.
        // Cela force un recalcul propre des données lors du prochain appel GET.
        $cachePool->invalidateTags(['conseilsCache']);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);// 204 No Content
    }
}
