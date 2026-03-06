<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class OpenMeteoService
{
    private const GEOCODING_URL = 'https://geocoding-api.open-meteo.com/v1/search';
    private const WEATHER_URL   = 'https://api.open-meteo.com/v1/forecast';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly TagAwareCacheInterface $cache
    ){}
       
    /*
     * Récupère la météo d'une ville en 2 étapes :
     * 1) Géocodage pour récupérer lat/lon
     * 2) Météo pour récupérer les données météo à partir de lat/lon
     */
    public function getMeteo(string $city): array
    {
        // On normalise la ville pour éviter les doublons dans le cache (ex: "Paris" vs "paris")
        $normalizedCity = mb_strtolower(trim($city));

        if ($normalizedCity === '') {
            throw new \InvalidArgumentException('La ville est obligatoire.');
        }

        // On génère une clé de cache unique pour chaque ville
        $cacheKey = 'meteo_' . md5($normalizedCity);

        /* On utilise le cache pour éviter de faire des requêtes à l'API à chaque fois. 
           Si les données sont en cache, on les retourne directement. 
           Sinon, on exécute la fonction de rappel pour récupérer les données et les stocker en cache. */
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($city, $normalizedCity) {

            // Durée du cache : 1 heure 
            $item->expiresAfter(3600);
            $item->tag('meteoCache');

            // 1) Géocodage Open-Meteo pour récupérer lat/lon
            $geoResponse = $this->httpClient->request('GET', self::GEOCODING_URL, [
                'query' => [
                    'name'     => $normalizedCity,
                    'count'    => 1, // On ne veut que le premier résultat
                    'language' => 'fr',
                ]
            ]);

            $geo = $geoResponse->toArray(false); // false pour ne pas lever d'exception en cas de code HTTP 4xx ou 5xx

            if (empty($geo['results'])) {
                throw new \RuntimeException("Ville introuvable : $city");
            }

            // On prend les coordonnées du premier résultat
            $lat = $geo['results'][0]['latitude'];
            $lon = $geo['results'][0]['longitude'];

            // 2) Météo Open-Meteo
            // On demande la météo actuelle, l'humidité horaire, et les heures de lever/coucher du soleil
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

            // On convertit les heures de lever/coucher du soleil timestamp Unix en format lisible
            $sunrise = date('H:i', $weather['daily']['sunrise'][0]);
            $sunset  = date('H:i', $weather['daily']['sunset'][0]);

            $code = $weather['current_weather']['weathercode']; // détermine le label (ensoleillé, nuageux, etc.)

            // 3) On renvoie un tableau propre, prêt à être retourné en JSON par l'API avec [0] → maintenant
            return [
                'temp'          => $weather['current_weather']['temperature'],
                'humidity'      => $weather['hourly']['relativehumidity_2m'][0],
                'precipitation' => $weather['hourly']['precipitation'][0],
                'wind'          => $weather['current_weather']['windspeed'],
                'sunrise'       => $sunrise,
                'sunset'        => $sunset,
                'label'         => $this->getLabel($code),
                'city'          => $city, 
            ];
        });
    }

    /*
     * Convertit le code météo en label lisible (ensoleillé, nuageux, etc.)
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
