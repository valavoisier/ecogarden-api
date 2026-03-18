<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
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
        [$statusCode, $data] = $this->resolveException($exception);
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
    private function resolveException(\Throwable $exception): array
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

        // Autres erreurs HTTP (400, 405, etc.)
        // HttpExceptionInterface fournit déjà un code HTTP → on le réutilise.
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

