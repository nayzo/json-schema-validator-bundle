<?php

declare(strict_types=1);

namespace Nayzo\JsonSchemaValidatorBundle\EventSubscriber;

use Symfony\Component\HttpKernel\Controller\ErrorController;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class JsonSchemaRequestValidationEventSubscriber extends AbstractJsonSchemaValidationEventSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if ($event->getController() instanceof ErrorController) {
            return;
        }

        if (!$this->isValidationSupported()) {
            return;
        }

        $request = $event->getRequest();

        $validator = $this->validatorFactory->build($this->validatorContract->getContractFile());

        $validator->validate($request, $request->getPathInfo(), $request->getMethod());
    }
}
