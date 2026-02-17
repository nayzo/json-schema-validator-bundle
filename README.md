JsonSchemaValidatorBundle
============================

The **JsonSchemaValidatorBundle** is a Symfony bundle that validate request payload and response with Json Schema for PSR7 & PSR17.

###### This Bundle is compatible with **Symfony >= 5.0**

Installation
------------

Install the bundle:

```
$ composer require nayzo/json-schema-validator-bundle
```

Usage
-----

#### Configuration:

```yaml
# config/packages/nayzo_json_schema_validator.yaml
nayzo_json_schema_validator:
    resources:
        my_account:
            contract_file_path: '%kernel.project_dir%/resources/api_contract/myaccount1.yaml'
            enable_default_request_validation_subscriber: true  # default to false
            enable_default_response_validation_subscriber: true # default to false

        my_second_account:
            contract_file_path: '%kernel.project_dir%/resources/api_contract/myaccount2.yaml'

    enable_default_validation_exception_subscriber: true  # default to false. (if activated, the default validation exception subscriber will be triggered and executed).
    validation_exception_status_code: 400 # default to 422 
```

#### Define the Validation Support (mandatory):
Note that if no validation support is found (exp: `MyAccountValidationSupport`), no request/response validation subscriber will be triggered or executed.

```php
<?php

use Nayzo\JsonSchemaValidatorBundle\Configurator\ValidationSubscriberSupportInterface;

final class MyAccountValidationSupport implements ValidationSubscriberSupportInterface
{
    public function __construct(private RequestStack $requestStack)
    {
    }
    
    public function support(): bool
    {
        // some code logic here... example:
        return '/v1/my-account' === $this->requestStack->getMainRequest()->getPathInfo();
    }

    public static function getResourceName(): string
    {
        return 'my_account';  // this value represents the resource name.
    }
}

```

#### Use default the request/response validation subscribers:
The **JsonSchemaValidatorBundle** defines two default validation subscribers: one for requests and another for responses.  
These subscribers are responsible for validating `request` and `response` schemas.  
To activate them, you must enable the corresponding flags: `enable_default_request_validation_subscriber` and `enable_default_response_validation_subscriber`, by setting their values to `true`.
  
If you prefer to use your own custom validation subscriber instead of the default ones provided by the bundle, you must disable the default subscribers by setting the `enable_default_request_validation_subscriber` flag to `false`, and then define the custom validation subscriber you wish to use.

Below is an example of usage without the default request/response validation subscribers.

```php
<?php
    // EventSubscriber
    
    use Nayzo\JsonSchemaValidatorBundle\Validator\ValidatorFactory;
    
    public function __construct(private JsonSchemaValidatorContractInterface $myAccountContract)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }
    
    public function onKernelController(ControllerEvent $event): void
    {       
        if ($event->getController() instanceof ErrorController) {
            return;
        }

        $request = $event->getRequest();
        $path = $this->myAccountContract->getContractFile();  // '/some/path/my-contract.yaml'
        
        $validatorFactory = new ValidatorFactory();
        $validator = $validatorFactory->build($path);
        $validator->validate($request, $request->getPathInfo(), $request->getMethod());
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $path = $this->myAccountContract->getContractFile();  // '/some/path/my-contract.yaml'
        
        $validatorFactory = new ValidatorFactory();
        $validator = $validatorFactory->build($path);
        $validator->validate($response, $request->getPathInfo(), $request->getMethod());
    }
```

#### Contract Service Dependency Injection:

```php
<?php

use Nayzo\JsonSchemaValidatorBundle\Contract\JsonSchemaValidatorContractInterface;

    public function __construct(
        private JsonSchemaValidatorContractInterface $myAccountContract, // represent "my_account" in "resources" configuration
        private JsonSchemaValidatorContractInterface $mySecondAccountContract, // represent "my_second_account" in "resources" configuration
    ) {
    }
    
    public function something(): array
    {
        $this->myAccountContract->getContractFile();        // '/some/path/my-contract.yaml'
        $this->mySecondAccountContract->getContractFile();  // '/some/path/my-second-contract.yaml'
    }

// Note: the "Contract" suffix must be added to the contract variable to be loaded correctly.
```

#### Exceptions
By default, the value of the `enable_default_validation_exception_subscriber` flag is set to `false`. If activated (set to `true`), the default validation exception subscriber will be triggered and executed.
  
To use your own custom validation exception subscriber, you must disable the `enable_default_validation_exception_subscriber` flag by setting its value to `false` and implement a subscriber that listens to `kernel.exception` events.
  
Be aware that when an `exception` is thrown **Before** the validation of the `request` or `response` implemented by the **JsonSchemaValidatorBundle** (for example, an access denied), it will be directly intercepted by the application's or framework's `kernel.exception` and will not be captured by the bundle's validation workflow.

#### OpenAPI
Official OpenAPI documentation here:

OpenAPI Specification (official):
➡️ https://spec.openapis.org/oas/v3.0.1.html

OpenAPI Guide (Swagger):
➡️ https://swagger.io/specification

##### Example of openapi implementation:

```yaml
openapi: 3.0.0
info:
    title: 'Some Title'
    version: 1.0.0
paths:
    /v1/foo:
        get:
            summary: 'Some summary'
            description: 'Some description'
            parameters:
                -   name: uuid
                    in: query
                    required: true
                    schema:
                        type: string

            responses:
                '200':
                    description: 'Some description'
                    content:
                        application/json:
                            schema:
                                $ref: '#/components/schemas/CustomComponent'
                '401':
                    description: Unauthorized
                '403':
                    description: Forbidden
                '404':
                    description: Resource Not Found
                '422':
                    description: Unprocessable Content
                '500':
                    description: Internal server error

        post:
            summary: 'Some summary'
            description: 'Some description'
            requestBody:
                required: true
                content:
                    application/json:
                        schema:
                            type: object
                            required:
                                - name
                            properties:
                                name:
                                    type: string
                            
            responses:
                '200':
                    description: 'Some description'
                    content:
                        application/json:
                            schema:
                                $ref: '#/components/schemas/CustomComponent'
                '401':
                    description: Unauthorized
                '403':
                    description: Forbidden
                '404':
                    description: Resource Not Found
                '422':
                    description: Unprocessable Content
                '500':
                    description: Internal server error

components:
    schemas:
        CustomComponent:
            type: object
            additionalProperties: false
            required:
                - id
                - name
                - status
            properties:
                id:
                    type: integer
                name:
                    type: string
                status:
                    type: string
                    enum: [enabled, disabled, deleted]
```
