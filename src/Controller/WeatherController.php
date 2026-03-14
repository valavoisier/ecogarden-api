<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\OpenMeteoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
final class WeatherController extends AbstractController
{
    public function __construct(
        private readonly OpenMeteoService $openMeteo
    ) {}

    /** 
     * Retourne la météo actuelle d'une ville passée dans l'URL.
     *
     * Méthode : GET  
     * URL     : /api/meteo/{city}  
     * Accès   : ROLE_USER
     *
     * Exemple :
     * GET /api/meteo/Paris
     *
     * Exemple de réponse :
     * {
     *   "temperature": 18.5,
     *   "humidite": 62,
     *   "precipitation": 0.0,
     *   "vent": 12.3,
     *   "lever_soleil": "06:47",
     *   "coucher_soleil": "21:12",
     *   "conditions": "soleil",
     *   "ville": "Paris"
     * }
     *
     * Codes de réponse :
     * - 200 : Succès
     * - 400 : Ville vide ou invalide
     * - 401 : Token manquant ou invalide
     * - 404 : Ville introuvable
     * - 500 : Erreur interne (API Open‑Meteo)
     *
     * @param string $city Nom de la ville
     * @return JsonResponse
     */
    #[Route('/meteo/{city}', name: 'api_meteo_city', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getMeteoForCity(?string $city): JsonResponse
    {
        $meteo = $this->openMeteo->getMeteo($city);
        return new JsonResponse($meteo, Response::HTTP_OK);
    }

    /**
     * Retourne la météo de la ville associée à l'utilisateur connecté.
     *
     * Méthode : GET  
     * URL     : /api/meteo  
     * Accès   : ROLE_USER
     *
     * Exemple :
     * GET /api/meteo
     *
     * Exemple de réponse :
     * {
     *   "temperature": 18.5,
     *   "humidite": 62,
     *   "precipitation": 0.0,
     *   "vent": 12.3,
     *   "lever_soleil": "06:47",
     *   "coucher_soleil": "21:12",
     *   "conditions": "soleil",
     *   "ville": "Paris"
     * }
     *
     * Codes de réponse :
     * - 200 : Succès
     * - 400 : Aucune ville définie pour l'utilisateur
     * - 401 : Token manquant ou invalide
     * - 404 : Ville introuvable
     * - 500 : Erreur interne (API Open‑Meteo)
     *
     * @return JsonResponse     
     */
    #[Route('/meteo', name: 'api_meteo_user_city', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getMeteoForUser(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User || !$user->getCity()) {
            throw new BadRequestHttpException('Aucune ville définie pour cet utilisateur.');
        }

        $meteo = $this->openMeteo->getMeteo($user->getCity());
        return new JsonResponse($meteo, Response::HTTP_OK);
    }
}

