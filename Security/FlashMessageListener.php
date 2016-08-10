<?php
namespace Vanio\UserBundle\Security;

use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\FOSUserEvents;
use HWI\Bundle\OAuthBundle\Event\FormEvent;
use HWI\Bundle\OAuthBundle\Event\GetResponseUserEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
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
    private $skipNextLoginMessage = false;

    public function __construct(UrlGeneratorInterface $urlGenerator, Session $session)
    {
        $this->urlGenerator = $urlGenerator;
        $this->session = $session;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
            FOSUserEvents::REGISTRATION_CONFIRMED => 'onRegistrationConfirmed',
            'hwi_oauth.registration.success' => 'onRegistrationSuccess',
            'hwi_oauth.connect.confirmed' => 'onConnectConfirmed',
        ];
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        if (!$this->skipNextLoginMessage) {
            $this->addFlashMessage(FlashMessage::TYPE_SUCCESS, 'security.flash.logged_in');
            $this->skipNextLoginMessage = false;
        }
    }

    public function onRegistrationConfirmed(FilterUserResponseEvent $event)
    {
        $response = $event->getResponse();

        if (
            !$response instanceof RedirectResponse
            || $response->getTargetUrl() !== $this->urlGenerator->generate('fos_user_registration_confirmed')
        ) {
            $this->addFlashMessage(FlashMessage::TYPE_SUCCESS, 'registration.flash.confirmed');
        }
    }

    public function onRegistrationSuccess(FormEvent $event)
    {
        $response = $event->getResponse();

        if (
            !$response instanceof RedirectResponse
            || $response->getTargetUrl() !== $this->urlGenerator->generate('fos_user_registration_check_email')
        ) {
            $this->addFlashMessage(FlashMessage::TYPE_SUCCESS, 'registration.flash.user_created');
            $this->skipNextLoginMessage = true;
        }
    }

    public function onConnectConfirmed(GetResponseUserEvent $event)
    {
        if ($event->getResponse() instanceof RedirectResponse) {
            $this->addFlashMessage(FlashMessage::TYPE_SUCCESS, 'connect.account_connected', [], 'HWIOAuthBundle');
        }
    }

    private function addFlashMessage(string $type, string $message, array $parameters = [], $domain = 'FOSUserBundle')
    {
        $this->session->getFlashBag()->add($type, new FlashMessage($message, $parameters, $domain));
    }
}
