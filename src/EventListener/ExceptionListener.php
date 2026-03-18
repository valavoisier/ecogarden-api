<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

#[AsEventListener(event: ExceptionEvent::class, method: 'onExceptionEvent')]
final class ExceptionListener
{
    /**
     * Point d’entrée du listener : intercepté à chaque exception levée par Symfony.
     *
     * - On récupère l’exception déclenchée
     * - On la convertit en un format JSON cohérent pour l’API
     * - On remplace la réponse HTTP par défaut par une réponse JSON standardisée
     */
    public function onExceptionEvent(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();// Récupère l’exception qui a été lancée dans le kernel
        // On délègue la logique de résolution à une méthode dédiée
        [$statusCode, $data] = $this->resolveException($exception, $event->getRequest()->getPathInfo(), $event->getRequest()->getMethod());
        // On remplace la réponse HTTP par défaut de Symfony par une réponse JSON propre et uniforme
        $event->setResponse(new JsonResponse($data, $statusCode));
    }

    /**
     * Analyse l’exception et retourne :
     * - un code HTTP adapté
     * - un tableau de données JSON structuré contenant les informations d’erreur
     * Cette méthode centralise toute la logique de gestion d’erreurs
     * pour garantir une réponse API cohérente, quel que soit le type d’exception.
     * @return array{int, array<string, mixed>}
     */
    private function resolveException(\Throwable $exception, string $path = '', string $method = ''): array
    {
        // 401 - Non authentifié : (ex : token invalide ou absent)
        if ($exception instanceof AuthenticationException || $exception instanceof UnauthorizedHttpException) {
            return [Response::HTTP_UNAUTHORIZED, ['error' => 'Authentification requise.']];
        }

        // 403 - Accès refusé : (ex : rôle insuffisant)
        if ($exception instanceof AccessDeniedHttpException) {
            return [Response::HTTP_FORBIDDEN, ['error' => "Accès refusé : vous n'avez pas les droits nécessaires."]];
        }

        // 404 - Ressource non trouvée 
        if ($exception instanceof NotFoundHttpException) {
            return [Response::HTTP_NOT_FOUND, ['error' => $exception->getMessage() ?: 'Ressource non trouvée.']];
        }

        // 422 - Erreurs de validation - données invalides envoyées par le client
        if ($exception instanceof UnprocessableEntityHttpException) {
            $decoded = json_decode($exception->getMessage(), true);
            $body = is_array($decoded) ? ['errors' => $decoded] : ['error' => $exception->getMessage()];
            return [Response::HTTP_UNPROCESSABLE_ENTITY, $body];
        }

        // 400 - Paramètre invalide sur GET /api/conseil/{mois}
        // Quand le client envoie un numéro hors de la plage 1–12, le regex de la route GET
        // ne correspond pas, mais les routes PUT/DELETE (avec \d+) correspondent → Symfony lève
        // un 405. On le convertit en 400 (bad request) puisqu'il s'agit bien d'un paramètre invalide.
        // preg_match pour vérifier que le path correspond à /api/conseil/{mois} avec un numéro (même si invalide)
        if ($exception instanceof MethodNotAllowedHttpException
            && $method === 'GET'
            && preg_match('#^/api/conseil/\d+$#', $path) === 1
        ) {
            return [Response::HTTP_BAD_REQUEST, ['error' => 'Le mois doit être compris entre 1 et 12.']];
        }

        // Pour toutes les erreurs HTTP standard (400, 403, 404, 405...),
        // on réutilise le code fourni par Symfony et on renvoie un JSON propre.
        // HttpExceptionInterface fournit le code HTTP et le message d’erreur
        if ($exception instanceof HttpExceptionInterface) {
            return [
                $exception->getStatusCode(),
                ['error' => $exception->getMessage() ?: 'Erreur HTTP.']
            ];
        }

        // 500 - Erreur interne  non gérée explicitement
        // On évite de divulguer des détails techniques.
        return [Response::HTTP_INTERNAL_SERVER_ERROR, ['error' => 'Une erreur interne est survenue.']];
    }
}

