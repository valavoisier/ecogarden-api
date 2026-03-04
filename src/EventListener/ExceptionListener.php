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
    public function onExceptionEvent(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        [$statusCode, $data] = $this->resolveException($exception);

        $event->setResponse(new JsonResponse($data, $statusCode));
    }

    /**
     * @return array{int, array<string, mixed>}
     */
    private function resolveException(\Throwable $exception): array
    {
        // 401 - Non authentifié
        if ($exception instanceof AuthenticationException || $exception instanceof UnauthorizedHttpException) {
            return [Response::HTTP_UNAUTHORIZED, ['error' => 'Authentification requise.']];
        }

        // 403 - Accès refusé
        if ($exception instanceof AccessDeniedHttpException) {
            return [Response::HTTP_FORBIDDEN, ['error' => "Accès refusé : vous n'avez pas les droits nécessaires."]];
        }

        // 404 - Ressource non trouvée
        if ($exception instanceof NotFoundHttpException) {
            return [Response::HTTP_NOT_FOUND, ['error' => $exception->getMessage() ?: 'Ressource non trouvée.']];
        }

        // 422 - Erreurs de validation
        if ($exception instanceof UnprocessableEntityHttpException) {
            $decoded = json_decode($exception->getMessage(), true);
            $body = is_array($decoded) ? ['errors' => $decoded] : ['error' => $exception->getMessage()];
            return [Response::HTTP_UNPROCESSABLE_ENTITY, $body];
        }

        // Autres erreurs HTTP (400, 405, etc.)
        if ($exception instanceof HttpExceptionInterface) {
            return [
                $exception->getStatusCode(),
                ['error' => $exception->getMessage() ?: 'Erreur HTTP.']
            ];
        }

        // 500 - Erreur interne
        return [Response::HTTP_INTERNAL_SERVER_ERROR, ['error' => 'Une erreur interne est survenue.']];
    }
}

