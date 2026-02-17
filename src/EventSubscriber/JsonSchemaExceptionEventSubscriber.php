<?php

declare(strict_types=1);

namespace Nayzo\JsonSchemaValidatorBundle\EventSubscriber;

use Nayzo\JsonSchemaValidatorBundle\Exception\ValidationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class JsonSchemaExceptionEventSubscriber implements EventSubscriberInterface
{
    public function __construct(private int $validationExceptionStatusCode)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onValidationException',
        ];
    }

    public function onValidationException(ExceptionEvent $event): void
    {
        if (!$event->getThrowable() instanceof ValidationException) {
            return;
        }

        $message = $event->getThrowable()->getMessage();
        $decoded = json_decode($message);
        $responseBody = ['error' => $decoded ?? $message];
        $response = new JsonResponse($responseBody, $this->validationExceptionStatusCode);

        $event->setResponse($response);
    }
}
