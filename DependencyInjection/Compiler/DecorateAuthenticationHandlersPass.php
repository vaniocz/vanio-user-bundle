<?php
namespace Vanio\UserBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DecorateAuthenticationHandlersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container
            ->getDefinition('vanio_user.routing.router')
            ->setAbstract(false)
            ->setDecoratedService('router');
        $this->decorateAuthenticationHandler(
            $container,
            'vanio_user.security.authentication_failure_handler',
            'security.authentication.failure_handler'
        );
        $this->decorateAuthenticationHandler(
            $container,
            'vanio_user.security.authentication_success_handler',
            'security.authentication.success_handler'
        );

        if (
            $container->getParameter('vanio_user.social_authentication')
            && $container->hasParameter('hwi_oauth.connect')
            && $container->getParameter('hwi_oauth.connect')
        ) {
            $this->decorateAuthenticationHandler(
                $container,
                'vanio_user.security.social_authentication_failure_handler',
                'security.authentication.failure_handler'
            );
        }
    }

    /**
     * Manual decoration of the original abstract service (prototype for actual default failure handler).
     * Builtin decoration cannot be used because DecoratorServicePass is handled after ResolveChildDefinitionsPass.
     */
    private function decorateAuthenticationHandler(
        ContainerBuilder $container,
        string $decoratorId,
        string $decoratedId
    ): void {
        $container->setDefinition(
            "{$decoratorId}.inner",
           (clone $container->getDefinition($decoratedId))->setAbstract(false)
        );
        $container->setDefinition(
            $decoratedId,
            $container->getDefinition($decoratorId)
        );
        $container->removeDefinition($decoratorId);
    }
}
