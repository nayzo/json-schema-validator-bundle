<?php

declare(strict_types=1);

namespace Nayzo\JsonSchemaValidatorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\HttpFoundation\Response;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('nayzo_json_schema_validator');
        /** @var ArrayNodeDefinition|NodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->children()
            ->arrayNode('resources')
                ->cannotBeEmpty()
                ->info('List of OpenApi contract resources')
                ->ignoreExtraKeys()
                ->useAttributeAsKey('contract_name')
                ->arrayPrototype()
                    ->children()
                        ->scalarNode('contract_file_path')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->info('Contract file absolute path')
                        ->end()
                        ->booleanNode('enable_default_request_validation_subscriber')
                            ->defaultFalse()
                            ->info('Use the default request validation subscriber')
                        ->end()
                        ->booleanNode('enable_default_response_validation_subscriber')
                            ->defaultFalse()
                            ->info('Use the default response validation subscriber')
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->booleanNode('enable_default_validation_exception_subscriber')
                ->defaultFalse()
                ->info('Use the default validation exception subscriber')
            ->end()
            ->integerNode('validation_exception_status_code')
                ->defaultValue(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->info('Override the default validation exception status code')
            ->end()
        ->end();

        return $treeBuilder;
    }
}
