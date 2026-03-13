<?php

namespace App\Controller;

use App\Entity\Conseil;
use App\Repository\ConseilRepository;
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
     * Cette méthode permet de récupérer l'ensemble des conseils de jardinage (route non demandée dans spécifications techniques, elle est optionnelle!)
     *  
     * Méthode : DELETE
     * URL     : /api/conseils  
     * Rôle    : ROLE_ADMIN
     *
     * * Paramètres :
     * - page  : numéro de page (défaut : 1)
     * - limit : nombre d’éléments par page (défaut : 10)
     * 
     * Exemple de requête :
     * GET /api/conseils?page=2&limit=10
     * 
     * Exemple de réponse :
     * {
     *    "data": [
     *      {
     *          "id": 1,
     *          "contenu": "Plantez vos tomates après les saints de glace.",
     *          "mois": [5, 6]
     *      }
     *     ],                               
     *     "pagination": { "page": 2, "limit": 10, "total": 42, "pages": 5 }   
     * }
     * 
     * Codes de réponse :
     * - 200 : Succès
     * 
     * @return JsonResponse 
     */
    #[Route('/api/conseils', name: 'conseil', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour accéder à cette ressource')]  
    public function getAllConseils(ConseilRepository $conseilRepository, Request $request, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $page  = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $idCache = "getAllConseils-" . $page . "-" . $limit;
        $result  = $cachePool->get($idCache, function (ItemInterface $item) use ($conseilRepository, $page, $limit) {
            //tag générique pour tous les conseils, permet d'invalider tout le cache lié aux conseils en cas de création, mise à jour ou suppression
            $item->tag('conseilsCache');
            //echo ("l'élément n'est pas encore en cache");
            //24h de cache pour éviter de surcharger la base de données, les conseils ne changent pas tous les jours
            $item->expiresAfter(86400);
            //accès aux méthodes du repository pour récupérer les conseils paginés et le nombre total pour construire les métadonnées de pagination 
            return [
                'conseils' => $conseilRepository->findAllPaginated($page, $limit),
                'total'    => $conseilRepository->countAll(),
            ];
        });
        //accès aux clés du tableau $result pour construire la réponse JSON avec la liste des conseils et le nombre total pour calculer les pages
        return $this->json([
            'data'       => $result['conseils'],
            'pagination' => [
                'page'  => $page,
                'limit' => $limit,
                'total' => $result['total'],
                'pages' => (int) ceil($result['total'] / $limit),
            ],
        ]);
    }

    /** 
     *  Cette méthode permet de récupérer les conseils d’un mois donné (1–12)
     * 
     * Méthode : DELETE
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
    #[Route('/api/conseil/{mois}', name: 'conseil_by_month', methods: ['GET'], requirements: ['mois' => '[1-9]|1[0-2]'])] 
    public function getConseilsByMonth(int $mois, ConseilRepository $conseilRepository, Request $request, TagAwareCacheInterface $cachePool): JsonResponse 
    { 
        $page  = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $idCache = "getConseilsByMonth-" . $mois . "-" . $page . "-" . $limit;
        $result  = $cachePool->get($idCache, function (ItemInterface $item) use ($conseilRepository, $mois, $page, $limit) {
            $item->tag(['conseilsCache', "conseilsCache-month-{$mois}"]);
            $item->expiresAfter(86400);
            return [
                'conseils' => $conseilRepository->findByMonthPaginated($mois, $page, $limit),
                'total'    => $conseilRepository->countByMonth($mois),
            ];
        });

        return $this->json([
            'data'       => $result['conseils'],
            'pagination' => [
                'page'  => $page,
                'limit' => $limit,
                'total' => $result['total'],
                'pages' => (int) ceil($result['total'] / $limit),
            ],
        ]);
    }

    /** 
     *  Cette méthode permet de récupérer les conseils du mois en cours
     * 
     * Méthode : DELETE
     * URL     : /api/conseil 
     * Rôle    : ROLE_USER
     *
     * Exemple :
     * GET /api/conseil
     *
     *  Exemple de réponse :
     * {
     *   "data": [...],
     *   "pagination": {"page": 1, "limit": 10, "total": 6, "pages": 1}
     *   }
     * }
     *
     * Codes de réponse :
     * - 200 : Succès
     * @return JsonResponse 
     */ 
    #[Route('/api/conseil', name: 'conseil_current_month', methods: ['GET'])] 
    public function getConseilsCurrentMonth(ConseilRepository $conseilRepository, Request $request, TagAwareCacheInterface $cachePool): JsonResponse 
    { 
        $mois  = (int) date('n');
        $page  = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $idCache = "getConseilsCurrentMonth-" . $mois . "-" . $page . "-" . $limit; //cache distinctif pour chaque mois et page, évite problème de cache non expiré au changement de mois
        $result  = $cachePool->get($idCache, function (ItemInterface $item) use ($conseilRepository, $mois, $page, $limit) {
            $item->tag(['conseilsCache', "conseilsCache-month-{$mois}"]);
            $item->expiresAfter(86400);
            return [
                'conseils' => $conseilRepository->findByMonthPaginated($mois, $page, $limit),
                'total'    => $conseilRepository->countByMonth($mois),
            ];
        });
        //accès aux clés du tableau $result pour construire la réponse JSON avec la liste des conseils et le nombre total pour calculer les pages
        return $this->json([
            'data'       => $result['conseils'],
            'pagination' => [
                'page'  => $page,
                'limit' => $limit,
                'total' => $result['total'],
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
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un conseil')]     
    public function createConseil( 
        Request $request, 
        EntityManagerInterface $entityManager, 
        ValidatorInterface $validator,
        TagAwareCacheInterface $cachePool
        ): JsonResponse { 
        // Récupération du JSON envoyé par le client 
        $data = json_decode($request->getContent(), true); 
        if ($data === null) { 
            throw new BadRequestHttpException('JSON invalide.'); // error 400
        } 
        // Création de l'entité 
        $conseil = new Conseil(); 
        $conseil->setContenu($data['contenu'] ?? '');
        $conseil->setMois($data['mois'] ?? []); 
        // Validation 
        $errors = $validator->validate($conseil); 

        if (count($errors) > 0) { 
            $messages = []; 
            foreach ($errors as $error) { 
                $messages[$error->getPropertyPath()] = $error->getMessage(); 
            } 
            throw new UnprocessableEntityHttpException(json_encode($messages));// error 422
        }

        // Sauvegarde en base 
        $entityManager->persist($conseil); 
        $entityManager->flush();

        // Invalidation du cache après création
        $cachePool->invalidateTags(['conseilsCache']);

        // Retourne l'objet créé avec un statut 201 
        return $this->json($conseil, Response::HTTP_CREATED);
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
    #[Route('/api/conseil/{id}', name: 'conseil_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour mettre à jour un conseil')]
    public function updateConseil(
        int $id,
        Request $request,
        ConseilRepository $conseilRepository,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cachePool
    ): JsonResponse {
        // Vérifier si le conseil existe
        $conseil = $conseilRepository->find($id);

        if (!$conseil) {
            throw new NotFoundHttpException('Conseil non trouvé.');// error 404
        }

        // Récupération du JSON envoyé
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            throw new BadRequestHttpException('JSON invalide.');// error 400
        }

        // Mise à jour des champs
        $conseil->setContenu($data['contenu'] ?? $conseil->getContenu());
        $conseil->setMois($data['mois'] ?? $conseil->getMois());

        // Validation
        $errors = $validator->validate($conseil);

        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
            throw new UnprocessableEntityHttpException(json_encode($messages));// error 422
        }

        // Sauvegarde
        $em->flush();

        // Invalidation du cache après mise à jour
        $cachePool->invalidateTags(['conseilsCache']);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
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
    #[Route('/api/conseil/{id}', name: 'conseil_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un conseil')]
    public function deleteConseil(
        int $id,
        ConseilRepository $conseilRepository,
        EntityManagerInterface $entityManager,
        TagAwareCacheInterface $cachePool
    ): JsonResponse {
        $conseil = $conseilRepository->find($id);

        if (!$conseil) {
            throw new NotFoundHttpException('Conseil non trouvé.');// error 404
        }

        $entityManager->remove($conseil);
        $entityManager->flush();

        // Invalidation du cache après suppression
        $cachePool->invalidateTags(['conseilsCache']);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Vide manuellement le cache des conseils (administration).
     * Le cache est normalement invalidé automatiquement à chaque POST, PUT ou DELETE.
     * Cette route sert de mécanisme de secours (ex : modification directe en base).
     *
     * Méthode : DELETE
     * URL     : /api/conseils/cache
     * Rôle    : ROLE_ADMIN
     *
     * Codes de réponse :
     * - 204 : Cache invalidé avec succès (aucun contenu)
     *
     * @param TagAwareCacheInterface $cachePool
     * @return JsonResponse
     */
    #[Route('/api/conseils/cache', name: 'conseil_clear_cache', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour vider le cache')]
    public function clearCache(TagAwareCacheInterface $cachePool): JsonResponse
    {
        $cachePool->invalidateTags(['conseilsCache']);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
