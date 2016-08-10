<?php
namespace Vanio\UserBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PassTargetPathPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->getParameter('vanio_user.pass_target_path.enabled')) {
            return;
        }

        $targetPathResolverReference = new Reference('vanio_user.security.target_path_resolver');
        $container
            ->getDefinition('vanio_user.routing.router')
            ->setAbstract(false)
            ->setDecoratedService('router');
        $container
            ->getDefinition('security.authentication.failure_handler')
            ->setClass('%vanio_user.security.authentication_failure_handler.class%')
            ->addMethodCall('setTargetPathResolver', [$targetPathResolverReference]);
        $container
            ->getDefinition('security.authentication.success_handler')
            ->setClass('%vanio_user.security.authentication_success_handler.class%')
            ->addArgument($targetPathResolverReference);
    }
}
