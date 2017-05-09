<?php
namespace Vanio\UserBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Vanio\UserBundle\Form\SocialRegistrationFormType;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder;
        /* @noinspection PhpUndefinedMethodInspection */
        $treeBuilder->root('vanio_user')
            ->children()
                ->scalarNode('firewall_name')->defaultNull()->end()
                ->booleanNode('email_only')->defaultFalse()->end()
                ->booleanNode('custom_storage_validation')->defaultFalse()->end()
                ->booleanNode('use_flash_notifications')->defaultTrue()->end()
                ->scalarNode('registration_target_path')->defaultNull()->end()
                ->arrayNode('pass_target_path')
                    ->canBeEnabled()
                    ->children()
                        ->booleanNode('default_target_path')->defaultValue('/')->end()
                        ->booleanNode('target_path_parameter')->defaultValue('_target_path')->end()
                        ->arrayNode('ignored_routes')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('ignored_route_prefixes')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
                ->booleanNode('social_authentication')->defaultNull()->end()
                ->arrayNode('social_registration_form')
                    ->addDefaultsIfNotSet()
                    ->fixXmlConfig('validation_group')
                    ->children()
                        ->scalarNode('type')->defaultValue(SocialRegistrationFormType::class)->end()
                        ->scalarNode('name')->defaultValue('hwi_oauth_registration_form')->end()
                        ->arrayNode('validation_groups')
                            ->prototype('scalar')->end()
                            ->defaultValue(['Profile', 'SocialRegistration'])
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
