<?php

declare(strict_types=1);

namespace Nayzo\JsonSchemaValidatorBundle\DependencyInjection;

use Nayzo\JsonSchemaValidatorBundle\Configurator\ValidationSubscriberSupportInterface;
use Nayzo\JsonSchemaValidatorBundle\Contract\JsonSchemaValidatorContract;
use Nayzo\JsonSchemaValidatorBundle\Contract\JsonSchemaValidatorContractInterface;
use Nayzo\JsonSchemaValidatorBundle\EventSubscriber\JsonSchemaExceptionEventSubscriber;
use Nayzo\JsonSchemaValidatorBundle\EventSubscriber\JsonSchemaRequestValidationEventSubscriber;
use Nayzo\JsonSchemaValidatorBundle\EventSubscriber\JsonSchemaResponseValidationEventSubscriber;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use function Symfony\Component\String\u;

class NayzoJsonSchemaValidatorExtension extends Extension implements CompilerPassInterface
{
    public const VALIDATION_SUPPORT_TAG = 'nayzo.json_schema_validator.support';

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $contractResources = $config['resources'];

        foreach ($contractResources as $contractName => $resource) {
            $contractFilePath = $resource['contract_file_path'];
            if (!file_exists($contractFilePath)) {
                throw new \InvalidArgumentException(sprintf('Contract file "%s" does not exist', $contractFilePath));
            }

            $contractId = $this->createValidatorContractService($container, $contractName, $contractFilePath);

            $this->handleRequestValidationSubscriber(
                $container,
                $contractId,
                $contractName,
                $resource['enable_default_request_validation_subscriber']
            );
            $this->handleResponseValidationSubscriber(
                $container,
                $contractId,
                $contractName,
                $resource['enable_default_response_validation_subscriber']
            );
        }

        if ($config['enable_default_validation_exception_subscriber']) {
            $container
                ->register(JsonSchemaExceptionEventSubscriber::class, JsonSchemaExceptionEventSubscriber::class)
                ->addArgument($config['validation_exception_status_code'])
                ->setAutoconfigured(true);
        }

        $container->registerForAutoconfiguration(ValidationSubscriberSupportInterface::class)
            ->addTag(self::VALIDATION_SUPPORT_TAG);
    }

    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds(self::VALIDATION_SUPPORT_TAG) as $id => $tags) {
            $contractName = $id::getResourceName();
            $requestValidatorSubscriberId = $this->resolveValidatorSubscriberId('request', $contractName);
            if ($container->has($requestValidatorSubscriberId)) {
                $container->getDefinition($requestValidatorSubscriberId)
                    ->addMethodCall('setValidationSubscriberSupport', [$container->getDefinition($id)]);
            }

            $responseValidatorSubscriberId = $this->resolveValidatorSubscriberId('response', $contractName);
            if ($container->has($responseValidatorSubscriberId)) {
                $container->getDefinition($responseValidatorSubscriberId)
                    ->addMethodCall('setValidationSubscriberSupport', [$container->getDefinition($id)]);
            }
        }
    }

    private function createValidatorContractService(
        ContainerBuilder $container,
        string $contractName,
        string $contractFilePath,
    ): string {
        $contractId = sprintf('nayzo.json_schema_validator_contract.%s', mb_strtolower($contractName));

        $container
            ->register($contractId, JsonSchemaValidatorContract::class)
            ->addArgument($contractFilePath)
            ->setAutowired(true);

        $alias = sprintf('%s $%sContract', JsonSchemaValidatorContractInterface::class, u($contractName)->camel());

        $container->setAlias($alias, $contractId);

        return $contractId;
    }

    private function handleRequestValidationSubscriber(
        ContainerBuilder $container,
        string $contractId,
        string $contractName,
        bool $isEnabled,
    ): void {
        $requestValidatorSubscriberId = $this->resolveValidatorSubscriberId('request', $contractName);
        if (!$isEnabled) {
            $container->removeDefinition($requestValidatorSubscriberId);

            return;
        }

        if ($container->has($requestValidatorSubscriberId)) {
            return;
        }

        $container
            ->register($requestValidatorSubscriberId, JsonSchemaRequestValidationEventSubscriber::class)
            ->setArgument('$validatorContract', $container->getDefinition($contractId))
            ->setAutowired(true)
            ->setAutoconfigured(true);
    }

    private function handleResponseValidationSubscriber(
        ContainerBuilder $container,
        string $contractId,
        string $contractName,
        bool $isEnabled,
    ): void {
        $responseValidatorSubscriberId = $this->resolveValidatorSubscriberId('response', $contractName);
        if (!$isEnabled) {
            $container->removeDefinition($responseValidatorSubscriberId);

            return;
        }

        if ($container->has($responseValidatorSubscriberId)) {
            return;
        }

        $container
            ->register($responseValidatorSubscriberId, JsonSchemaResponseValidationEventSubscriber::class)
            ->setArgument('$validatorContract', $container->getDefinition($contractId))
            ->setAutowired(true)
            ->setAutoconfigured(true);
    }

    private function resolveValidatorSubscriberId(string $type, string $contractName): string
    {
        return sprintf(
            'nayzo.json_schema_%s_validator_subscriber.%s',
            mb_strtolower($type),
            mb_strtolower($contractName)
        );
    }
}
