<?php

declare(strict_types=1);

namespace Nayzo\JsonSchemaValidatorBundle\Tests\EventSubscriber;

use Nayzo\JsonSchemaValidatorBundle\Configurator\ValidationSubscriberSupportInterface;
use Nayzo\JsonSchemaValidatorBundle\Contract\JsonSchemaValidatorContract;
use Nayzo\JsonSchemaValidatorBundle\EventSubscriber\JsonSchemaRequestValidationEventSubscriber;
use Nayzo\JsonSchemaValidatorBundle\Exception\ValidationException;
use Nayzo\JsonSchemaValidatorBundle\Validator\Validator;
use Nayzo\JsonSchemaValidatorBundle\Validator\ValidatorFactory;
use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use League\OpenAPIValidation\PSR7\ResponseValidator;
use League\OpenAPIValidation\PSR7\RoutedServerRequestValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class JsonSchemaRequestValidationEventSubscriberTest extends TestCase
{
    private ValidatorFactory $validatorFactory;
    private JsonSchemaValidatorContract $validatorContract;
    private ValidationSubscriberSupportInterface $validationSubscriberSupport;
    private JsonSchemaRequestValidationEventSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->validatorFactory = $this->createMock(ValidatorFactory::class);
        $this->validatorContract = $this->createMock(JsonSchemaValidatorContract::class);
        $this->validationSubscriberSupport = $this->createMock(ValidationSubscriberSupportInterface::class);

        $this->subscriber = new JsonSchemaRequestValidationEventSubscriber(
            $this->validatorFactory,
            $this->validatorContract
        );

        $this->subscriber->setValidationSubscriberSupport($this->validationSubscriberSupport);
    }

    public function testItSubscribesToKernelControllerEvent(): void
    {
        $this->assertArrayHasKey('kernel.controller', $this->subscriber::getSubscribedEvents());
    }

    public function testItValidatesARequest(): void
    {
        $request = new Request([], [], [], [], [], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/test']);

        $event = $this->createMock(ControllerEvent::class);
        $event->method('getController')->willReturn(fn () => new \stdClass());
        $event->method('getRequest')->willReturn($request);

        $this->validationSubscriberSupport->method('support')->willReturn(true);

        $validator = $this->createMock(Validator::class);
        $validator->expects($this->once())->method('validate')->with($request, '/test', 'POST');

        $this->validatorContract->method('getContractFile')->willReturn('contract.json');
        $this->validatorFactory->method('build')->with('contract.json')->willReturn($validator);

        $this->subscriber->onKernelController($event);
    }

    public function testValidatorValidatesRequest(): void
    {
        $requestValidator = $this->createMock(RoutedServerRequestValidator::class);
        $responseValidator = $this->createMock(ResponseValidator::class);
        $validator = new Validator($requestValidator, $responseValidator);

        $request = new Request([], [], [], [], [], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/test',
            'HTTP_HOST' => 'localhost',
            'HTTPS' => 'off',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => '80',
        ]);

        $this->assertTrue($validator->validate($request, '/test', 'POST'));
    }

    public function testValidatorValidatesResponse(): void
    {
        $requestValidator = $this->createMock(RoutedServerRequestValidator::class);
        $responseValidator = $this->createMock(ResponseValidator::class);
        $validator = new Validator($requestValidator, $responseValidator);

        $response = new Response();
        $this->assertTrue($validator->validate($response, '/test', 'GET'));
    }

    public function testValidatorThrowsExceptionOnValidationFailure(): void
    {
        $requestValidator = $this->createMock(RoutedServerRequestValidator::class);
        $responseValidator = $this->createMock(ResponseValidator::class);
        $validator = new Validator($requestValidator, $responseValidator);

        $request = new Request([], [], [], [], [], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/test',
            'HTTP_HOST' => 'localhost',
            'HTTPS' => 'off',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => '80',
        ]);

        $requestValidator->method('validate')->willThrowException(new ValidationFailed('Validation error'));

        $this->expectException(ValidationException::class);

        $validator->validate($request, '/test', 'POST');
    }
}
