<?php
namespace Vanio\UserBundle\EventListener;

use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Vanio\UserBundle\Mailer\TwigSwiftMailer;
use Vanio\UserBundle\Model\User;

class EmailChangeListener implements EventSubscriberInterface
{
    /** @var string|null */
    private $oldEmail;

    /** @var TokenGeneratorInterface */
    private $tokenGenerator;

    /** @var TwigSwiftMailer */
    private $mailer;

    /** @var UserManagerInterface */
    private $userManager;

    public function __construct(TokenGeneratorInterface $tokenGenerator, TwigSwiftMailer $mailer, UserManagerInterface $userManager)
    {
        $this->tokenGenerator = $tokenGenerator;
        $this->mailer = $mailer;
        $this->userManager = $userManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FOSUserEvents::PROFILE_EDIT_INITIALIZE => 'onProfileEditInitialize',
            FOSUserEvents::PROFILE_EDIT_SUCCESS => 'onProfileEditSuccess',
            FOSUserEvents::PROFILE_EDIT_COMPLETED => 'onProfileEditCompleted',
        ];
    }

    public function onProfileEditInitialize(GetResponseUserEvent $event)
    {
        $this->oldEmail = $event->getUser()->getEmail();
    }

    public function onProfileEditSuccess(FormEvent $event)
    {
        /** @var User $user */
        $user = $event->getForm()->getData();

        if ($user->getEmail() !== $this->oldEmail) {
            $user->requestNewEmail($user->getEmail(), $this->tokenGenerator->generateToken());

            $this->mailer->sendNewEmailConfirmationMessage($user);
        }
    }

    public function onProfileEditCompleted(FilterUserResponseEvent $event)
    {
        $user = $event->getUser();
        $user->setEmail($this->oldEmail);

        $this->userManager->updateUser($user);
    }
}
