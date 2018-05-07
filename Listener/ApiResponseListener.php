<?php
namespace Vanio\UserBundle\Listener;

use FOS\UserBundle\Event\FilterGroupResponseEvent;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseNullableUserEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Event\UserEvent;
use FOS\UserBundle\FOSUserEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;
use Vanio\UserBundle\Model\User;
use Vanio\UserBundle\VanioUserEvents;
use Vanio\WebBundle\Serializer\Serializer;

/**
 * Responds with serialized responses instead of redirections
 */
class ApiResponseListener implements EventSubscriberInterface
{
    /** @var Serializer */
    private $serializer;

    /** @var TranslatorInterface */
    private $translator;

    /** @var int */
    private $resettingRetryTtl;

    /** @var bool */
    private $emailOnly;

    public function __construct(
        Serializer $serializer,
        TranslatorInterface $translator,
        int $resettingRetryTtl,
        bool $emailOnly = false
    ) {
        $this->serializer = $serializer;
        $this->translator = $translator;
        $this->resettingRetryTtl = $resettingRetryTtl;
        $this->emailOnly = $emailOnly;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        $okEvents = [
            FOSUserEvents::RESETTING_SEND_EMAIL_COMPLETED,
            FOSUserEvents::RESETTING_RESET_SUCCESS,
            FOSUserEvents::CHANGE_PASSWORD_SUCCESS,
            FOSUserEvents::PROFILE_EDIT_SUCCESS,
            FOSUserEvents::GROUP_EDIT_SUCCESS,
            VanioUserEvents::REGISTRATION_CONFIRMATION_REQUESTED,
            VanioUserEvents::CHANGE_EMAIL_SUCCESS,
            VanioUserEvents::UNREGISTRATION_COMPLETED,
        ];

        return array_fill_keys($okEvents, ['respondWithOkResponse', -1024]) + [
            FOSUserEvents::REGISTRATION_SUCCESS => ['respondWithCreatedResponse', -1024],
            FOSUserEvents::GROUP_CREATE_SUCCESS => 'respondWithCreatedResponse',
            FOSUserEvents::REGISTRATION_CONFIRM => 'onRegistrationConfirm',
            FOSUserEvents::RESETTING_SEND_EMAIL_INITIALIZE => 'onResettingSendEmailInitialize',
            FOSUserEvents::RESETTING_RESET_REQUEST => 'onResettingResetRequest',
            FOSUserEvents::GROUP_DELETE_COMPLETED => 'onGroupDeleteCompleted',
            VanioUserEvents::REGISTRATION_CONFIRMATION_REQUEST => 'onRegistrationConfirmationRequest',
            VanioUserEvents::RESETTING_RESET_FAILURE => 'onResettingResetFailure',
            VanioUserEvents::CHANGE_EMAIL_INITIALIZE => 'onChangeEmailInitialize',
            VanioUserEvents::CHANGE_EMAIL_FAILURE => 'onChangeEmailFailure',
        ];
    }

    /**
     * @internal
     */
    public function onRegistrationConfirm(GetResponseUserEvent $event): void
    {
        if ($event->getUser()) {
            $this->respondWithOkResponse($event);
        } else {
            $this->respondWithNotFoundResponse(
                $event,
                $this->translateErrorMessage('registration.confirmation_token_not_found')
            );
        }
    }

    /**
     * @internal
     */
    public function onResettingSendEmailInitialize(GetResponseNullableUserEvent $event): void
    {
        $property = $this->emailOnly ? 'email' : 'username';
        $username = $event->getRequest()->request->get('username');

        if (trim($username) === '') {
            $this->respondWithUnprocessableEntityResponse(
                $event,
                $this->translateErrorMessage(sprintf('fos_user.%s.blank', $property), [], 'validators')
            );
        } elseif (!$event->getUser()) {
            $this->respondWithUnprocessableEntityResponse(
                $event,
                $this->translateErrorMessage("resetting.request.invalid_$property", ["%$property%" => $username])
            );
        } elseif ($event->getUser()->isPasswordRequestNonExpired($this->resettingRetryTtl)) {
            $this->respondWithOkResponse($event);
        }
    }

    /**
     * @internal
     */
    public function onResettingResetRequest(GetResponseUserEvent $event): void
    {
        if (!$event->getUser()->isAccountNonLocked()) {
            $this->respondWithUnprocessableEntityResponse(
                $event,
                $this->translateErrorMessage('Account is locked.', [], 'security')
            );
        }
    }

    /**
     * @internal
     */
    public function onGroupDeleteCompleted(FilterGroupResponseEvent $event): void
    {
        $response = $event->getResponse();
        $this->respondWithOkResponse($event);
        $okResonse = $event->getResponse();
        $response
            ->setStatusCode($okResonse->getStatusCode())
            ->setContent($okResonse->getContent())
            ->headers->replace($okResonse->headers->all());
    }

    /**
     * @internal
     */
    public function onRegistrationConfirmationRequest(GetResponseNullableUserEvent $event): void
    {
        if (!$event->getUser()) {
            $this->respondWithNotFoundResponse(
                $event,
                $this->translateErrorMessage('registration.user_not_found')
            );
        } elseif ($event->getUser()->isEnabled()) {
            $this->respondWithUnprocessableEntityResponse(
                $event,
                $this->translateErrorMessage('registration.already_confirmed')
            );
        }
    }

    /**
     * @internal
     */
    public function onResettingResetFailure(GetResponseNullableUserEvent $event): void
    {
        $this->respondWithNotFoundResponse(
            $event,
            $this->translateErrorMessage('resetting.confirmation_token_not_found')
        );
    }

    /**
     * @internal
     */
    public function onChangeEmailInitialize(GetResponseNullableUserEvent $event): void
    {
        if (!$event->getUser() instanceof User) {
            $this->respondWithNotFoundResponse(
                $event,
                $this->translateErrorMessage('change_email.confirmation_token_not_found')
            );
        }
    }

    /**
     * @internal
     */
    public function onChangeEmailFailure(FilterUserResponseEvent $event): void
    {
        $this->respondWithNotFoundResponse($event, $this->translateErrorMessage('change_email.email_already_used'));
    }

    /**
     * @internal
     * @param FilterGroupResponseEvent|FormEvent|GetResponseUserEvent $event
     */
    public function respondWithOkResponse($event): void
    {
        $this->respondWithSuccessResponse($event, 200);
    }

    /**
     * @internal
     * @param FormEvent|GetResponseUserEvent $event
     */
    public function respondWithCreatedResponse($event): void
    {
        $this->respondWithSuccessResponse($event, 201);
    }

    private function respondWithUnprocessableEntityResponse(GetResponseUserEvent $event, string $error): void
    {
        $format = $event->getRequest()->getRequestFormat();

        if ($format === 'html') {
            return;
        }

        $data = [
            'code' => 422,
            'message' => 'Unprocessable Entity',
            'errors' => [$error],
        ];
        $event->setResponse(new Response($this->serializer->serialize($data, $format), 422));
    }

    /**
     * @param GetResponseUserEvent|FilterUserResponseEvent $event
     * @param string $error
     */
    private function respondWithNotFoundResponse(UserEvent $event, string $error): void
    {
        $format = $event->getRequest()->getRequestFormat();

        if ($format === 'html') {
            return;
        }

        $data = [
            'code' => 404,
            'message' => 'Not Found',
            'errors' => [$error],
        ];
        $event->setResponse(new Response($this->serializer->serialize($data, $format), 404));
    }

    /**
     * @param FilterGroupResponseEvent|FormEvent|GetResponseUserEvent $event
     * @param int $statusCode
     */
    private function respondWithSuccessResponse($event, int $statusCode): void
    {
        $format = $event->getRequest()->getRequestFormat();

        if ($format !== 'html') {
            $event->setResponse(new Response($this->serializer->serialize(['success' => true], $format), $statusCode));
        }
    }

    /**
     * @param string $id
     * @param mixed[] $parameters
     * @param string $domain
     * @return string
     */
    private function translateErrorMessage(string $id, array $parameters = [], string $domain = 'FOSUserBundle'): string
    {
        return $this->translator->trans($id, $parameters, $domain);
    }
}
