<?php

declare(strict_types=1);

namespace Nayzo\JsonSchemaValidatorBundle;

use Nayzo\JsonSchemaValidatorBundle\DependencyInjection\NayzoJsonSchemaValidatorExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class JsonSchemaValidatorBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new NayzoJsonSchemaValidatorExtension();
    }
}
