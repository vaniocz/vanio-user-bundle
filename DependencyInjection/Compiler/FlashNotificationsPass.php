<?php
namespace Vanio\UserBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FlashNotificationsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->getParameter('vanio_user.use_flash_notifications')) {
            return;
        }

        $firewallName = $container->getParameter('vanio_user.firewall_name');

        if ($container->hasDefinition("security.logout_listener.$firewallName")) {
            $logoutListenerDefinition = $container->getDefinition("security.logout_listener.$firewallName");
            $container
                ->getDefinition('vanio_user.security.notifying_logout_success_handler')
                ->setAbstract(false)
                ->setDecoratedService((string) $logoutListenerDefinition->getArgument(2));
        }

        $container
            ->getDefinition('vanio_user.listener.flash_message_listener')
            ->setAbstract(false)
            ->addTag('kernel.event_subscriber');
    }
}
