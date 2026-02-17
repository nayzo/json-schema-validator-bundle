<?php

declare(strict_types=1);

namespace Nayzo\JsonSchemaValidatorBundle\EventSubscriber;

use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class JsonSchemaResponseValidationEventSubscriber extends AbstractJsonSchemaValidationEventSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->isValidationSupported()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $validator = $this->validatorFactory->build($this->validatorContract->getContractFile());

        $validator->validate($response, $request->getPathInfo(), $request->getMethod());
    }
}
