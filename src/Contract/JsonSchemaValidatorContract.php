<?php

declare(strict_types=1);

namespace Nayzo\JsonSchemaValidatorBundle\Contract;

class JsonSchemaValidatorContract implements JsonSchemaValidatorContractInterface
{
    public function __construct(private string $contractFile)
    {
    }

    public function getContractFile(): string
    {
        return $this->contractFile;
    }
}
