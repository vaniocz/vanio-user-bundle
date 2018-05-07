<?php
namespace Vanio\UserBundle\Listener;

use FOS\UserBundle\Event\FormEvent as FOSUserFormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;
use HWI\Bundle\OAuthBundle\Event\FormEvent as HWIOAuthFormEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Vanio\UserBundle\VanioUserEvents;

/**
 * Sends confirmation emails upon successful registration.
 */
class EmailConfirmationListener implements EventSubscriberInterface
{
    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FOSUserEvents::REGISTRATION_INITIALIZE => 'onRegistrationInitialize',
            FOSUserEvents::REGISTRATION_SUCCESS => 'onRegistrationSuccess',
            'hwi_oauth.registration.success' => 'onRegistrationSuccess',
        ];
    }

    /**
     * @internal
     */
    public function onRegistrationInitialize(GetResponseUserEvent $event)
    {
        $event->getUser()->setEnabled(false);
    }

    /**
     * @internal
     * @param HWIOAuthFormEvent|FOSUserFormEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function onRegistrationSuccess(
        $event,
        string $eventName,
        EventDispatcherInterface $eventDispatcher
    ) {
        $form = $event->getForm();

        if (!$form->getData()->isEnabled()) {
            $confirmationEvent = new FOSUserFormEvent($event->getForm(), $event->getRequest());
            $eventDispatcher->dispatch(VanioUserEvents::REGISTRATION_CONFIRMATION_REQUESTED, $confirmationEvent);

            if (!$response = $confirmationEvent->getResponse()) {
                throw new \RuntimeException(
                    'You need to enable email confirmation inside your fos_user configuration.'
                );
            }

            $event->setResponse($response);
        }
    }
}
