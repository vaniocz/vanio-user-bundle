<?php
namespace Vanio\UserBundle\Security;

use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\HttpUtils;

class RedirectOnRegistrationSuccess implements EventSubscriberInterface
{
    /** @var HttpUtils */
    private $httpUtils;

    /** @var string */
    private $targetPath;

    public function __construct(HttpUtils $httpUtils, string $targetPath)
    {
        $this->httpUtils = $httpUtils;
        $this->targetPath = $targetPath;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FOSUserEvents::REGISTRATION_SUCCESS => ['onRegistrationSuccess', PHP_INT_MIN],
            FOSUserEvents::REGISTRATION_CONFIRM => ['onRegistrationSuccess', PHP_INT_MIN],
            'hwi_oauth.registration.success' => 'onRegistrationSuccess',
            'hwi_oauth.connect.confirmed' => 'onRegistrationSuccess',
        ];
    }

    /**
     * @internal
     * @param FormEvent|GetResponseUserEvent $event
     */
    public function onRegistrationSuccess($event)
    {
        if (!$event->getResponse()) {
            $event->setResponse($this->httpUtils->createRedirectResponse($event->getRequest(), $this->targetPath));
        }
    }
}
