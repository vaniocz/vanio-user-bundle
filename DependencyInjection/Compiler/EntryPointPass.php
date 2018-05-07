<?php
namespace Vanio\UserBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EntryPointPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (
            !$container->getParameter('vanio_user.use_flash_notifications')
            && !$container->getParameter('vanio_user.pass_target_path.enabled')
        ) {
            return;
        }

        $firewallName = $container->getParameter('vanio_user.firewall_name');
        $exceptionListenerDefinition = $container->getDefinition("security.exception_listener.$firewallName");

        if ($authenticationEntryPointReference = $exceptionListenerDefinition->getArgument(4)) {
            $container
                ->getDefinition('vanio_user.security.authentication_entry_point')
                ->setAbstract(false)
                ->setDecoratedService((string) $authenticationEntryPointReference);
        }
    }
}
