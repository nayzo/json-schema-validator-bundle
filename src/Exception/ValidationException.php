<?php

declare(strict_types=1);

namespace Nayzo\JsonSchemaValidatorBundle\Exception;

use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use League\OpenAPIValidation\Schema\Exception\SchemaMismatch;

class ValidationException extends \Exception
{
    public static function buildException(ValidationFailed $exception): ValidationException
    {
        $previous = $exception;
        $output['message'] = self::formatMessage($exception->getMessage());

        while ($exception = $exception->getPrevious()) {
            if (!$exception->getMessage()) {
                continue;
            }

            $output['details'] = self::formatMessage($exception->getMessage());

            if ($exception instanceof SchemaMismatch && !empty($breadCrumb = $exception->dataBreadCrumb())) {
                if ($field = implode('.', $breadCrumb->buildChain())) {
                    $output['field'] = $field;
                }
            }
        }

        return new ValidationException(json_encode($output), 0, $previous);
    }

    private static function formatMessage(string $message): string
    {
        return preg_replace(['/"+/', "/'{2,}/"], "'", $message);
    }
}
