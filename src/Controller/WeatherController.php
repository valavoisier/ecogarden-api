<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\OpenMeteoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
final class WeatherController extends AbstractController
{
    public function __construct(
        private readonly OpenMeteoService $openMeteo
    ) {}

    /*
     * Version API : récupère la météo d'une ville passée dans l'URL.
     * Equivalent de ton index() initial, mais renvoie du JSON.
     */
    #[Route('/meteo/{city}', name: 'api_meteo_city', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getMeteoForCity(string $city): JsonResponse
    {
        try {
            $meteo = $this->openMeteo->getMeteo($city);
            return new JsonResponse($meteo, Response::HTTP_OK);

        } catch (\RuntimeException $e) {
            // Ville introuvable
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);

        } catch (\InvalidArgumentException $e) {
            // Ville vide ou invalide
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);

        } catch (\Throwable $e) {
            // Erreur API Open-Meteo ou autre
            return new JsonResponse(
                ['error' => 'Erreur lors de la récupération de la météo.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /*
     * Version API : récupère la météo de la ville de l'utilisateur connecté.
     * Equivalent de ton index() mais sans formulaire, basé sur User::city.
     */
    #[Route('/meteo', name: 'api_meteo_user_city', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getMeteoForUser(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getCity()) {
            return new JsonResponse(
                ['error' => 'Aucune ville définie pour cet utilisateur.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $meteo = $this->openMeteo->getMeteo($user->getCity());
            return new JsonResponse($meteo, Response::HTTP_OK);

        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            return new JsonResponse(
                ['error' => 'Erreur lors de la récupération de la météo.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}

