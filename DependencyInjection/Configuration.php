<?php
namespace Vanio\UserBundle\DependencyInjection;

use FOS\UserBundle\Util\LegacyFormHelper;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Vanio\UserBundle\Form\ChangeEmailFormType;
use Vanio\UserBundle\Form\SocialRegistrationFormType;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder;
        $rootNode = $treeBuilder->root('vanio_user');
        /* @noinspection PhpUndefinedMethodInspection */
        $rootNode
            ->children()
                ->scalarNode('firewall_name')->defaultNull()->end()
                ->booleanNode('email_only')->defaultFalse()->end()
                ->booleanNode('custom_storage_validation')->defaultFalse()->end()
                ->booleanNode('use_flash_notifications')->defaultTrue()->end()
                ->scalarNode('registration_target_path')->defaultNull()->end()
                ->arrayNode('pass_target_path')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('default_target_path')->defaultValue('/')->end()
                        ->scalarNode('target_path_parameter')->defaultValue('_target_path')->end()
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
                            ->defaultValue(['Profile', 'SocialRegistration', 'Default'])
                        ->end()
                    ->end()
                ->end()
            ->end();

        $this->addChangeEmailSection($rootNode);

        return $treeBuilder;
    }

    private function addChangeEmailSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('change_email')
                    ->addDefaultsIfNotSet()
                    ->canBeUnset()
                    ->children()
                        ->arrayNode('confirmation')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')->defaultNull()->end()
                                ->scalarNode('template')->defaultValue('@VanioUser/ChangeEmail/email.html.twig')->end()
                                ->arrayNode('from_email')
                                    ->canBeUnset()
                                    ->children()
                                        ->scalarNode('address')->isRequired()->cannotBeEmpty()->end()
                                        ->scalarNode('sender_name')->isRequired()->cannotBeEmpty()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('form')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('type')->defaultValue(ChangeEmailFormType::class)->end()
                                ->scalarNode('name')->defaultValue('vanio_user_change_email_form')->end()
                                ->arrayNode('validation_groups')
                                    ->prototype('scalar')->end()
                                    ->defaultValue(array('ChangeEmail', 'Default'))
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
