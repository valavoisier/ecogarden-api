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

final class ConseilController extends AbstractController
{
    /**
     * Cette méthode permet de récupérer l'ensemble des conseils de jardinage
     *  *
     * Méthode : GET  
     * URL     : /api/conseils  
     * Rôle    : ROLE_USER
     *
     * Exemple de réponse :
     * [
     *   {
     *     "id": 1,
     *     "contenu": "Plantez vos tomates après les saints de glace.",
     *     "mois": [5, 6]
     *   }
     * ]
     *
     * @return JsonResponse 
     */
    #[Route('/api/conseils', name: 'conseil', methods: ['GET'])]
    public function getAllConseils(ConseilRepository $conseilRepository): JsonResponse
    {
        $conseils = $conseilRepository->findAll();
        //utilisation automatique du serializer (installation du symfony/serializer-pack) pour convertir les objets en JSON
        return $this->json($conseils);
    }

    /** 
     *  Cette méthode permet de récupérer les conseils d’un mois donné (1–12)
     * * Méthode : GET  
     * URL     : /api/conseil/{mois}  
     * Rôle    : ROLE_USER
     *
     * Paramètres :
     * - mois : entier entre 1 et 12
     *
     * Exemple :
     * GET /api/conseil/5
     *
     * @param int $mois
     * @return JsonResponse 
     */ 
    #[Route('/api/conseil/{mois}', name: 'conseil_by_month', methods: ['GET'], requirements: ['mois' => '[1-9]|1[0-2]'])] 
    public function getConseilsByMonth(int $mois, ConseilRepository $conseilRepository): JsonResponse 
    { 
        /* Vérification du mois non atteinte avec requierments, mais si on veut faire une vérification supplémentaire pour être sûr :
        if ($mois < 1 || $mois > 12) { 
            throw new BadRequestHttpException('Le mois doit être compris entre 1 et 12.');// error 400
        } */
        // Récupération des conseils
        $conseils = $conseilRepository->findByMonth($mois); 
        return $this->json($conseils); 
    }

    /** 
     *  Cette méthode permet de récupérer les conseils du mois en cours
     * 
     * Méthode : GET  
     * URL     : /api/conseil/current  
     * Rôle    : ROLE_USER
     *
     * Exemple :
     * GET /api/conseil/current
     *
     * @return JsonResponse 
     */ 
    #[Route('/api/conseil/current', name: 'conseil_current_month', methods: ['GET'])] 
    public function getConseilsCurrentMonth(ConseilRepository $conseilRepository): JsonResponse 
    { 
        $conseils = $conseilRepository->findByMonth((int) date('n'));
        return $this->json($conseils);
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
        ValidatorInterface $validator 
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
        ValidatorInterface $validator
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

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Cette méthode permet de supprimer un conseil existant
     * *
     * Méthode : DELETE  
     * URL     : /api/conseil/{id}  
     * Rôle    : ROLE_ADMIN
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
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $conseil = $conseilRepository->find($id);

        if (!$conseil) {
            throw new NotFoundHttpException('Conseil non trouvé.');// error 404
        }

        $entityManager->remove($conseil);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }    
}
