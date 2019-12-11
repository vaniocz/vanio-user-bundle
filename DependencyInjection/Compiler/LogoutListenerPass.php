<?php
namespace Vanio\UserBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Vanio\UserBundle\Listener\LogoutListener;

class LogoutListenerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $firewallName = $container->getParameter('vanio_user.firewall_name');
        $logoutListener = $container
            ->getDefinition("security.logout_listener.{$firewallName}")
            ->setClass(LogoutListener::class);
    }
}
