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
}
