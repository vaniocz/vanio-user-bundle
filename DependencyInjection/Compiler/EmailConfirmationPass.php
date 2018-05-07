<?php
namespace Vanio\UserBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Vanio\UserBundle\VanioUserEvents;

class EmailConfirmationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->getParameter('fos_user.registration.confirmation.enabled')) {
            return;
        }

        $container
            ->getDefinition('vanio_user.listener.email_confirmation_listener')
            ->setAbstract(false)
            ->addTag('kernel.event_subscriber');
        $container
            ->getDefinition('fos_user.listener.email_confirmation')
            ->clearTags()
            ->addTag('kernel.event_listener', [
                'event' => VanioUserEvents::REGISTRATION_CONFIRMATION_REQUESTED,
                'method' => 'onRegistrationSuccess',
            ]);
    }
}
