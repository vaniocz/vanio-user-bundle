<?php
namespace Vanio\UserBundle\Security;

use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Vanio\UserBundle\Mailer\TwigSwiftMailer;
use Vanio\UserBundle\Model\User;
use Vanio\WebBundle\Translation\FlashMessage;

class EmailChangeConfirmationListener implements EventSubscriberInterface
{
    /** @var string|null */
    private $oldEmail;

    /** @var TokenGeneratorInterface */
    private $tokenGenerator;

    /** @var TwigSwiftMailer */
    private $mailer;

    /** @var Session */
    private $session;

    public function __construct(TokenGeneratorInterface $tokenGenerator, TwigSwiftMailer $mailer, Session $session)
    {
        $this->tokenGenerator = $tokenGenerator;
        $this->mailer = $mailer;
        $this->session = $session;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FOSUserEvents::PROFILE_EDIT_INITIALIZE => 'onProfileEditInitialize',
            FOSUserEvents::PROFILE_EDIT_SUCCESS => 'onProfileEditSuccess',
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
            $user->setEmail($this->oldEmail);

            $this->mailer->sendChangeEmailConfirmationMessage($user);

            $this->session->getFlashBag()->add(
                FlashMessage::TYPE_WARNING,
                new FlashMessage('change_email.flash.confirmation_required', [], 'FOSUserBundle')
            );
        }
    }
}