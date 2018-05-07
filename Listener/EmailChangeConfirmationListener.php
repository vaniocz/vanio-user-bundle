<?php
namespace Vanio\UserBundle\Listener;

use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Vanio\UserBundle\Mailer\TwigSwiftMailer;
use Vanio\UserBundle\Model\User;
use Vanio\UserBundle\VanioUserEvents;

class EmailChangeConfirmationListener implements EventSubscriberInterface
{
    /** @var string|null */
    private $oldEmail;

    /** @var TokenGeneratorInterface */
    private $tokenGenerator;

    /** @var TwigSwiftMailer */
    private $mailer;

    public function __construct(TokenGeneratorInterface $tokenGenerator, TwigSwiftMailer $mailer)
    {
        $this->tokenGenerator = $tokenGenerator;
        $this->mailer = $mailer;
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FOSUserEvents::PROFILE_EDIT_INITIALIZE => 'onProfileEditInitialize',
            FOSUserEvents::PROFILE_EDIT_SUCCESS => 'onProfileEditSuccess',
        ];
    }

    /**
     * @internal
     */
    public function onProfileEditInitialize(GetResponseUserEvent $event): void
    {
        $this->oldEmail = $event->getUser()->getEmail();
    }

    /**
     * @internal
     * @param FormEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function onProfileEditSuccess(
        FormEvent $event,
        string $eventName,
        EventDispatcherInterface $eventDispatcher
    ): void {
        $user = $event->getForm()->getData();

        if (!$user instanceof User) {
            throw new \LogicException(sprintf(
                'Your User class needs to extend "%s" class when email change confirmation is enabled.',
                User::class
            ));
        }

        if ($user->getEmail() === $this->oldEmail) {
            return;
        }

        $user->requestNewEmail($user->getEmail(), $this->tokenGenerator->generateToken());
        $user->setEmail($this->oldEmail);
        $this->mailer->sendChangeEmailConfirmationMessage($user);
        $confirmationEvent = new GetResponseUserEvent($user, $event->getRequest());

        if ($response = $event->getResponse()) {
            $confirmationEvent->setResponse($response);
        }

        $eventDispatcher->dispatch(VanioUserEvents::CHANGE_EMAIL_CONFIRMATION_SENT, $confirmationEvent);

        if ($response = $confirmationEvent->getResponse()) {
            $event->setResponse($response);
        }
    }
}
