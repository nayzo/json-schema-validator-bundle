<?php

declare(strict_types=1);

namespace Nayzo\JsonSchemaValidatorBundle\Validator;

use Nayzo\JsonSchemaValidatorBundle\Exception\ValidationException;
use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use League\OpenAPIValidation\PSR7\OperationAddress;
use League\OpenAPIValidation\PSR7\ResponseValidator;
use League\OpenAPIValidation\PSR7\RoutedServerRequestValidator;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class Validator
{
    public function __construct(private RoutedServerRequestValidator $requestValidator, private ResponseValidator $responseValidator)
    {
    }

    public function validate(object $payload, string $path, string $method): bool
    {
        if (!in_array(
            strtoupper($method),
            [Request::METHOD_GET, Request::METHOD_POST, Request::METHOD_PUT, Request::METHOD_DELETE, Request::METHOD_PATCH, Request::METHOD_HEAD], true
        )) {
            throw new \InvalidArgumentException('Unsupported method: ' . $method);
        }

        $payload = $this->resolvePayload($payload);
        $operation = $this->getOperationAddress($path, $method);
        $validator = $payload instanceof ResponseInterface ? $this->responseValidator : $this->requestValidator;

        try {
            $validator->validate($operation, $payload);
        } catch (ValidationFailed $exception) {
            throw ValidationException::buildException($exception);
        }

        return true;
    }

    public function resolvePayload(object $message): MessageInterface
    {
        if ($message instanceof ResponseInterface || $message instanceof ServerRequestInterface) {
            return $message;
        }

        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

        if ($message instanceof Response) {
            return $psrHttpFactory->createResponse($message);
        }

        if ($message instanceof Request) {
            return $psrHttpFactory->createRequest($message);
        }

        throw new \InvalidArgumentException(sprintf('Unsupported %s object received', get_class($message)));
    }

    private function getOperationAddress(string $path, string $method): OperationAddress
    {
        $path = sprintf('/%s', ltrim($path, '/'));

        return new OperationAddress($path, strtolower($method));
    }
}
