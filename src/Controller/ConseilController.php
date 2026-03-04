<?php

namespace App\Controller;

use App\Repository\ConseilRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
final class ConseilController extends AbstractController
{
    #[Route('/api/conseils', name: 'conseil')]
    public function getAllConseils(ConseilRepository $conseilRepository): JsonResponse
    {
        $conseils = $conseilRepository->findAll();
        //utilisation automatique du serializer pour convertir les objets en JSON
        return $this->json($conseils);
    }
}
