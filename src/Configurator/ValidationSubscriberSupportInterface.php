<?php

declare(strict_types=1);

namespace Nayzo\JsonSchemaValidatorBundle\Configurator;

interface ValidationSubscriberSupportInterface
{
    public static function getResourceName(): string;

    public function support(): bool;
}
