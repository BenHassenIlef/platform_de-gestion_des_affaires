<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Gérer uniquement les erreurs 404
        if ($exception instanceof NotFoundHttpException) {
            // Vous pouvez personnaliser la réponse ici
            $response = new Response();
            $response->setContent('<h1>Page non trouvée</h1><p>Cette page n\'existe pas.</p>');
            $response->setStatusCode(Response::HTTP_NOT_FOUND);

            // Définissez la réponse dans l'événement
            $event->setResponse($response);
        }
    }
}
