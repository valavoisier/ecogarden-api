<?php

namespace App\Controller;

use App\Entity\Conseil;
use App\Repository\ConseilRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
final class ConseilController extends AbstractController

{
    /**
     * Cette méthode permet de récupérer l'ensemble des conseils 
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
     */ 
    #[Route('/api/conseil/{mois}', name: 'conseil_by_month', methods: ['GET'])] 
    public function getConseilsByMonth(int $mois, ConseilRepository $conseilRepository): JsonResponse 
    { 
        // Vérification du mois 
        if ($mois < 1 || $mois > 12) { 
            return $this->json( 
                ['error' => 'Le mois doit être compris entre 1 et 12.'], 
                400 
            ); 
        } // Récupération des conseils
        $conseils = $conseilRepository->findByMonth($mois); 
        return $this->json($conseils); 
    }

    /** 
     *  Cette méthode permet de créer un nouveau conseil 
     */ 
    #[Route('/api/conseil', name: 'conseil_create', methods: ['POST'])] 
    public function createConseil( Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator ): JsonResponse { 
        // Récupération du JSON envoyé par le client 
        $data = json_decode($request->getContent(), true); 
        if (!$data) { 
            return $this->json(['error' => 'JSON invalide'], 400); 
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
            return $this->json($messages, 400); 
        }

        // Sauvegarde en base 
        $entityManager->persist($conseil); 
        $entityManager->flush(); 

        // Retourne l'objet créé avec un statut 201 
        return $this->json($conseil, 201); }

    /**
     * Cette méthode permet de mettre à jour un conseil existant
     */
    #[Route('/api/conseil/{id}', name: 'conseil_update', methods: ['PUT'])]
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
            return $this->json(['error' => 'Conseil non trouvé'], 404);
        }

        // Récupération du JSON envoyé
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'JSON invalide'], 400);
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
            return $this->json($messages, 400);
        }

        // Sauvegarde
        $em->flush();

        return $this->json($conseil, 200);
    }

    /**
     * Cette méthode permet de supprimer un conseil existant
     */
    #[Route('/api/conseil/{id}', name: 'conseil_delete', methods: ['DELETE'])]
    public function deleteConseil(
        int $id,
        ConseilRepository $conseilRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $conseil = $conseilRepository->find($id);

        if (!$conseil) {
            return $this->json(['error' => 'Conseil non trouvé'], 404);
        }

        $em->remove($conseil);
        $em->flush();

        return $this->json(['message' => 'Conseil supprimé'], 200);
    }



    
}
