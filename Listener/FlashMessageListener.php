<?php
namespace Vanio\UserBundle\Listener;

use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\GetResponseNullableUserEvent;
use FOS\UserBundle\Event\GetResponseUserEvent as FosUserGetResponseUserEvent;
use FOS\UserBundle\Event\UserEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\User;
use HWI\Bundle\OAuthBundle\Event\FormEvent;
use HWI\Bundle\OAuthBundle\Event\GetResponseUserEvent as HwiOauthGetResponseUserEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Vanio\UserBundle\VanioUserEvents;
use Vanio\WebBundle\Translation\FlashMessage;

/**
 * Notifies about certain events.
 */
class FlashMessageListener implements EventSubscriberInterface
{
    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var Session */
    private $session;

    /** @var bool */
    private $emailOnly;

    /** @var bool */
    private $skipNextLoginMessage = false;

    public function __construct(UrlGeneratorInterface $urlGenerator, Session $session, bool $emailOnly = false)
    {
        $this->urlGenerator = $urlGenerator;
        $this->session = $session;
        $this->emailOnly = $emailOnly;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 10],
            SecurityEvents::INTERACTIVE_LOGIN => ['onInteractiveLogin', PHP_INT_MAX],
            FOSUserEvents::SECURITY_IMPLICIT_LOGIN => 'onImplicitLogin',
            FOSUserEvents::REGISTRATION_CONFIRM => 'onRegistrationConfirm',
            FOSUserEvents::REGISTRATION_CONFIRMED => 'onRegistrationConfirmed',
            FOSUserEvents::RESETTING_SEND_EMAIL_INITIALIZE => 'onResettingSendEmailInitialize',
            VanioUserEvents::REGISTRATION_CONFIRMATION_REQUEST => 'onRegistrationConfirmationRequest',
            VanioUserEvents::RESETTING_RESET_FAILURE => 'onResettingResetFailure',
            VanioUserEvents::CHANGE_EMAIL_CONFIRMATION_SENT => 'onChangeEmailConfirmationSent',
            VanioUserEvents::CHANGE_EMAIL_INITIALIZE => 'onChangeEmailInitialize',
            VanioUserEvents::CHANGE_EMAIL_FAILURE => 'onChangeEmailFailure',
            VanioUserEvents::CHANGE_EMAIL_COMPLETED => 'onChangeEmailCompleted',
            VanioUserEvents::UNREGISTRATION_COMPLETED => 'onUnregistrationCompleted',
            'hwi_oauth.registration.success' => 'onRegistrationSuccess',
            'hwi_oauth.connect.confirmed' => 'onConnectConfirmed',
            VanioUserEvents::ACCOUNT_DISCONNECTED => 'onAccountDisconnected',
        ];
    }

    /**
     * @internal
     */
    public function onRequest(GetResponseEvent $event): void
    {
        $this->disconnectApiFlashBag($event->getRequest());
    }

    /**
     * @internal
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $this->disconnectApiFlashBag($event->getRequest());

        if ($this->skipNextLoginMessage) {
            return;
        }

        $user = $event->getAuthenticationToken()->getUser();
        $message = $user instanceof User && !$user->getLastLogin()
            ? 'security.flash.first_logged_in'
            : 'security.flash.logged_in';
        $this->addFlashMessage(FlashMessage::TYPE_SUCCESS, $message);
        $this->skipNextLoginMessage = false;
    }

    /**
     * @internal
     */
    public function onImplicitLogin(UserEvent $event): void
    {
        $this->disconnectApiFlashBag($event->getRequest());
    }

    /**
     * @internal
     */
    public function onRegistrationConfirm(FosUserGetResponseUserEvent $event): void
    {
        if (!$event->getUser()) {
            $this->addFlashMessage(FlashMessage::TYPE_DANGER, 'registration.confirmation_token_not_found');
        }
    }

    /**
     * @internal
     */
    public function onRegistrationConfirmed(FilterUserResponseEvent $event): void
    {
        if (!$event->getResponse()->isRedirect($this->urlGenerator->generate('fos_user_registration_confirmed'))) {
            $this->addFlashMessage(FlashMessage::TYPE_SUCCESS, 'registration.flash.confirmed');
        }
    }

    /**
     * @internal
     */
    public function onResettingSendEmailInitialize(GetResponseNullableUserEvent $event): void
    {
        if ($event->getUser()) {
            return;
        }

        $property = $this->emailOnly ? 'email' : 'username';
        $this->addFlashMessage(
            FlashMessage::TYPE_DANGER,
            "resetting.request.invalid_$property",
            ["%$property%" => $event->getRequest()->request->get('username')]
        );

        if ($event->getRequest()->getRequestFormat() === 'html') {
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('fos_user_resetting_request')));
        }
    }

    /**
     * @internal
     */
    public function onRegistrationConfirmationRequest(GetResponseNullableUserEvent $event): void
    {
        if (!$event->getUser()) {
            $this->addFlashMessage(FlashMessage::TYPE_DANGER, 'registration.user_not_found');
        } elseif ($event->getUser()->isEnabled()) {
            $this->addFlashMessage(FlashMessage::TYPE_INFO, 'registration.already_confirmed');
        }
    }

    /**
     * @internal
     */
    public function onResettingResetFailure(): void
    {
        $this->addFlashMessage(FlashMessage::TYPE_DANGER, 'resetting.confirmation_token_not_found');
    }

    /**
     * @internal
     */
    public function onChangeEmailConfirmationSent(): void
    {
        $this->addFlashMessage(FlashMessage::TYPE_WARNING, 'change_email.flash.confirmation_sent');
    }

    /**
     * @internal
     */
    public function onChangeEmailInitialize(GetResponseNullableUserEvent $event): void
    {
        if (!$event->getUser() instanceof UserInterface) {
            $this->addFlashMessage(FlashMessage::TYPE_DANGER, 'change_email.confirmation_token_not_found');
        }
    }

    /**
     * @internal
     */
    public function onChangeEmailFailure(): void
    {
        $this->addFlashMessage(FlashMessage::TYPE_DANGER, 'change_email.email_already_used');
    }

    /**
     * @internal
     */
    public function onChangeEmailCompleted(): void
    {
        $this->addFlashMessage(FlashMessage::TYPE_SUCCESS, 'change_email.flash.success');
    }

    /**
     * @internal
     */
    public function onUnregistrationCompleted(): void
    {
        $this->addFlashMessage(FlashMessage::TYPE_SUCCESS, 'unregister.flash.success');
    }

    /**
     * @internal
     */
    public function onRegistrationSuccess(FormEvent $event): void
    {
        $user = $event->getForm()->getData();

        if ($user instanceof AdvancedUserInterface && $user->isEnabled()) {
            $this->addFlashMessage(FlashMessage::TYPE_SUCCESS, 'registration.flash.user_created');
            $this->skipNextLoginMessage = true;
        }
    }

    /**
     * @internal
     */
    public function onConnectConfirmed(HwiOauthGetResponseUserEvent $event): void
    {
        if ($event->getResponse() && $event->getResponse()->isRedirection()) {
            $this->addFlashMessage(
                FlashMessage::TYPE_SUCCESS,
                'connect.flash.account_connected',
                [],
                'HWIOAuthBundle'
            );
        }
    }

    /**
     * @internal
     */
    public function onAccountDisconnected(): void
    {
        $this->addFlashMessage(
            FlashMessage::TYPE_SUCCESS,
            'connect.flash.account_disconnected',
            [],
            'HWIOAuthBundle'
        );
    }

    private function disconnectApiFlashBag(Request $request): void
    {
        if ($request->getRequestFormat() !== 'html') {
            $flashes = $this->session->getFlashBag()->peekAll();
            $this->session->getFlashBag()->initialize($flashes);
        }
    }

    /**
     * @param string $type
     * @param string $message
     * @param mixed[] $parameters
     * @param string $domain
     */
    private function addFlashMessage(
        string $type,
        string $message,
        array $parameters = [],
        string $domain = 'FOSUserBundle'
    ): void {
        $this->session->getFlashBag()->add($type, new FlashMessage($type, $message, $parameters, $domain));
    }
}
