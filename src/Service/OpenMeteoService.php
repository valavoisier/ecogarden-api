<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service chargé de récupérer la météo d'une ville via l’API Open‑Meteo.
 *
 * Fonctionnement en 2 étapes :
 * 1) Géocodage : conversion du nom de ville → latitude / longitude
 * 2) Récupération de la météo à partir des coordonnées
 *
 * Le service utilise un cache pour éviter les appels répétés à l’API.
 */

class OpenMeteoService
{
    private const GEOCODING_URL = 'https://geocoding-api.open-meteo.com/v1/search';
    private const WEATHER_URL   = 'https://api.open-meteo.com/v1/forecast';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly TagAwareCacheInterface $cache
    ){}
       
    /**
     * Récupère la météo d'une ville.
     *
     * Étapes :
     * - Normalisation du nom de ville
     * - Utilisation du cache
     * - Géocodage (lat/lon)
     * - Récupération des données météo
     * - Formatage du tableau final
     *
     * @throws \UnexpectedValueException Si la ville est vide (incohérence en base)
     * @throws NotFoundHttpException     Si la ville n’existe pas
     */
    public function getMeteo(string $city): array
    {
        // On normalise la ville pour éviter les doublons dans le cache (ex: "Paris" vs "paris")
        //mb_strotolower pour gérer les accents (ex: "Évry" vs "évry") et trim pour supprimer les espaces avant/après
        $normalizedCity = mb_strtolower(trim($city));

        if ($normalizedCity === '') {
            throw new \UnexpectedValueException('La ville du compte utilisateur est vide (incohérence en base de données).');
        }

        // // Génération d’une clé de cache unique pour chaque ville
        $cacheKey = 'meteo_' . md5($normalizedCity);

        /* On utilise le cache pour éviter de faire des requêtes à l'API à chaque fois. 
           Si les données sont en cache, on les retourne directement. 
           Sinon, on exécute la fonction de rappel pour récupérer les données et les stocker en cache. */
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($city, $normalizedCity) {

            // Durée du cache : 1 heure 
            $item->expiresAfter(3600);
            $item->tag('meteoCache');
            //------------------------------------------------------------
            // 1) Géocodage : conversion du nom de ville → lat/lon
            //------------------------------------------------------------
            // On interroge l’endpoint de géocodage :
            // https://geocoding-api.open-meteo.com/v1/search
            // Paramètres :
            // - name : nom de la ville normalisé
            // - count : 1 → on ne garde que le premier résultat
            // - language : fr → résultats en français
            $geoResponse = $this->httpClient->request('GET', self::GEOCODING_URL, [
                'query' => [
                    'name'     => $normalizedCity,
                    'count'    => 1, 
                    'language' => 'fr',
                ]
            ]);
            // toArray(false) : ne lève pas d’exception automatique en cas d’erreur HTTP
            $geo = $geoResponse->toArray(false); 

            // Si aucun résultat → ville inconnue
            if (empty($geo['results'])) {
                throw new NotFoundHttpException("La ville est introuvable. Vérifiez l'orthographe ou essayez un nom différent.");
            }
            
            //Récupération des coordonnées du premier résultat
            $lat = $geo['results'][0]['latitude'];
            $lon = $geo['results'][0]['longitude'];

            //-------------------------------------------------------------
            // 2) Récupération de la météo via l’endpoint forecast
            // https://api.open-meteo.com/v1/forecast
            //-------------------------------------------------------------
             // Paramètres :
            // - latitude / longitude : coordonnées obtenues via le géocodage
            // - current_weather : météo actuelle
            // - hourly : humidité + précipitations heure par heure
            // - daily : lever / coucher du soleil
            // - timezone : auto → adaptée automatiquement
            // - timeformat : unixtime → timestamps Unix
            $weatherResponse = $this->httpClient->request('GET', self::WEATHER_URL, [
                'query' => [
                    'latitude'        => $lat,
                    'longitude'       => $lon,
                    'current_weather' => true,
                    'daily'           => 'sunrise,sunset',
                    'hourly'          => 'relativehumidity_2m,precipitation',
                    'timezone'        => 'auto',
                    'timeformat'      => 'unixtime',
                ]
            ]);

            $weather = $weatherResponse->toArray(false);

            // Conversion des timestamps Unix en heures lisibles (HH:MM)
            $sunrise = date('H:i', $weather['daily']['sunrise'][0]);
            $sunset  = date('H:i', $weather['daily']['sunset'][0]);

            // Code météo → label lisible (soleil, pluie, neige…)
            $code = $weather['current_weather']['weathercode']; 

            // ----------------------------------------------------------
            // 3) Construction du tableau final retourné par l’API
            // ----------------------------------------------------------
            //
            // Ce tableau est directement renvoyé en JSON par le WeatherController.
            // [0] → maintenant
            return [
                'temperature'          => $weather['current_weather']['temperature'],
                'humidite'      => $weather['hourly']['relativehumidity_2m'][0],
                'precipitation' => $weather['hourly']['precipitation'][0],
                'vent'          => $weather['current_weather']['windspeed'],
                'leve_soleil'       => $sunrise,
                'couche_soleil'        => $sunset,
                'conditions'         => $this->getLabel($code),
                'ville'          => $city, 
            ];
        });
    }

    /*
     * Convertit un code météo Open‑Meteo en label lisible. (ensoleillé, nuageux, etc.)
     * Voir la documentation Open-Meteo pour les codes météo : https://open-meteo.com/en/docs
     */
    private function getLabel(int $code): string
    {
        return match (true) {
            $code === 0 => 'soleil',
            in_array($code, [1, 2]) => 'nuageux',
            $code === 3 => 'couvert',
            in_array($code, [51, 53, 55, 61, 63, 65]) => 'pluie',
            in_array($code, [66, 67]) => 'verglas',
            in_array($code, [71, 73, 75]) => 'neige',
            default => '',
        };
    }
}
