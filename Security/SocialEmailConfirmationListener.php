<?php
namespace Vanio\UserBundle\Security;

use FOS\UserBundle\Event\FormEvent as FOSUserFormEvent;
use HWI\Bundle\OAuthBundle\Event\FormEvent as HWIOAuthFormEvent;
use HWI\Bundle\OAuthBundle\HWIOAuthEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Vanio\UserBundle\VanioUserEvents;

/**
 * Sends confirmation emails upon successful registration.
 */
class SocialEmailConfirmationListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [HWIOAuthEvents::REGISTRATION_SUCCESS => 'onRegistrationSuccess'];
    }

    public function onRegistrationSuccess(
        HWIOAuthFormEvent $event,
        string $eventName,
        EventDispatcherInterface $eventDispatcher
    ) {
        $form = $event->getForm();

        if (!$form->getData()->isEnabled()) {
            $confirmationEvent = new FOSUserFormEvent($event->getForm(), $event->getRequest());
            $eventDispatcher->dispatch(VanioUserEvents::REGISTRATION_CONFIRMATION_REQUESTED, $confirmationEvent);

            if (!$response = $confirmationEvent->getResponse()) {
                throw new \RuntimeException('You need to enable email confirmation inside your fos_user configuration.');
            }

            $event->setResponse($response);
        }
    }
}
