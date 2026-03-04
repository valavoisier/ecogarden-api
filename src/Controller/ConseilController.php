<?php

namespace App\Controller;

use App\Repository\ConseilRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
final class ConseilController extends AbstractController

{/**
  *Cette méthode permet de récupérer l'ensemble des conseils 
 */
    #[Route('/api/conseils', name: 'conseil', methods: ['GET'])]
    public function getAllConseils(ConseilRepository $conseilRepository): JsonResponse
    {
        $conseils = $conseilRepository->findAll();
        //utilisation automatique du serializer (installation du symfony/serializer-pack) pour convertir les objets en JSON
        return $this->json($conseils);
    }

    /** 
     *  Récupère les conseils d’un mois donné (1–12) 
    */ 
    #[Route('/api/conseils/{mois}', name: 'conseil_by_month', methods: ['GET'])] 
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

    
}
