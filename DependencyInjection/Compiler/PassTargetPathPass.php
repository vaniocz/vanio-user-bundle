<?php
namespace Vanio\UserBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PassTargetPathPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->getParameter('vanio_user.pass_target_path.enabled')) {
            return;
        }

        $targetPathResolverReference = new Reference('vanio_user.security.target_path_resolver');
        $container
            ->getDefinition('vanio_user.routing.router')
            ->setAbstract(false)
            ->setDecoratedService('router');
        $this->decorateAuthenticationFailureHandler($container);
        $this->decorateAuthenticationSuccessHandler($container);
    }

    /**
     * Manual decoration of the original abstract service (prototype for actual default failure handler).
     * Builtin decoration cannot be used because DecoratorServicePass is handled after ResolveChildDefinitionsPass.
     */
    private function decorateAuthenticationFailureHandler(ContainerBuilder $container)
    {
        $container->setDefinition(
            'vanio_user.security.authentication_failure_handler.inner',
           (clone $container->getDefinition('security.authentication.failure_handler'))->setAbstract(false)
        );
        $container->setDefinition(
            'security.authentication.failure_handler',
            $container->getDefinition('vanio_user.security.authentication_failure_handler')
        );
        $container->removeDefinition('vanio_user.security.authentication_failure_handler');
    }

    /**
     * Manual decoration of the original abstract service (prototype for actual default success handler).
     * Builtin decoration cannot be used because DecoratorServicePass is handled after ResolveChildDefinitionsPass.
     */
    private function decorateAuthenticationSuccessHandler(ContainerBuilder $container): void
    {
        $container->setDefinition(
            'vanio_user.security.authentication_success_handler.inner',
            (clone $container->getDefinition('security.authentication.success_handler'))->setAbstract(false)
        );
        $container->setDefinition(
            'security.authentication.success_handler',
            $container->getDefinition('vanio_user.security.authentication_success_handler')
        );
        $container->removeDefinition('vanio_user.security.authentication_success_handler');
    }
}
