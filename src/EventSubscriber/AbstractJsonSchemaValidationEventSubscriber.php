<?php

declare(strict_types=1);

namespace Nayzo\JsonSchemaValidatorBundle\EventSubscriber;

use Nayzo\JsonSchemaValidatorBundle\Configurator\ValidationSubscriberSupportInterface;
use Nayzo\JsonSchemaValidatorBundle\Contract\JsonSchemaValidatorContract;
use Nayzo\JsonSchemaValidatorBundle\Validator\ValidatorFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractJsonSchemaValidationEventSubscriber implements EventSubscriberInterface
{
    protected ?ValidationSubscriberSupportInterface $validationSubscriberSupport = null;

    public function __construct(protected ValidatorFactory $validatorFactory, protected JsonSchemaValidatorContract $validatorContract)
    {
    }

    public function setValidationSubscriberSupport(ValidationSubscriberSupportInterface $validationSubscriberSupport): void
    {
        $this->validationSubscriberSupport = $validationSubscriberSupport;
    }

    protected function isValidationSupported(): bool
    {
        if (null === $this->validationSubscriberSupport) {
            return false;
        }

        return $this->validationSubscriberSupport->support();
    }
}
