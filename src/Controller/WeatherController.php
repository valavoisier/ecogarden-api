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
        // Appel au service OpenMeteo pour récupérer les données météo de la ville.
        // Le service gère :
        // - la validation du nom de ville
        // - l'appel HTTP à l'API Open‑Meteo
        // - la transformation des données brutes en tableau exploitable
        $meteo = $this->openMeteo->getMeteo($city);
        // Retourne les données météo au format JSON avec un statut HTTP 200.
        return new JsonResponse($meteo, Response::HTTP_OK);//200 OK
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
        // Récupération de l'utilisateur connecté via le token JWT.
        $user = $this->getUser();

        // Vérifie que l'utilisateur est bien authentifié
        // et qu'une ville est définie dans son profil.
        // Sinon → erreur 400 Bad Request.
        if (!$user instanceof User || !$user->getCity()) {
            throw new BadRequestHttpException('Aucune ville définie pour cet utilisateur.');
        }
        // Appel au service OpenMeteo pour récupérer la météo
        // de la ville enregistrée dans le profil utilisateur.
        $meteo = $this->openMeteo->getMeteo($user->getCity());
        
        // Retourne les données météo au format JSON avec un statut HTTP 200.
        return new JsonResponse($meteo, Response::HTTP_OK);//200 OK
    }
}

