<?php
namespace Vanio\UserBundle\Controller;

use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseNullableUserEvent;
use FOS\UserBundle\Form\Factory\FactoryInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Vanio\UserBundle\Model\User;
use Vanio\UserBundle\VanioUserEvents;

class ChangeEmailController extends Controller
{
    use ResponseFormatTrait;

    public function confirmAction(Request $request, string $token): Response
    {
        /** @var User $user */
        $user = $this->userManager()->findUserBy(['newEmailConfirmationToken' => $token]);
        $event = new GetResponseNullableUserEvent($user, $request);
        $this->eventDispatcher()->dispatch(VanioUserEvents::CHANGE_EMAIL_INITIALIZE, $event);

        if ($response = $event->getResponse()) {
            return $response;
        } elseif (!$event->getUser() instanceof User) {
            return $this->createRedirectResponse($request);
        } elseif (!$this->validateNewEmail($user)) {
            $event = new FilterUserResponseEvent($user, $request, $this->createRedirectResponse($request));
            $this->eventDispatcher()->dispatch(VanioUserEvents::CHANGE_EMAIL_FAILURE, $event);
            $user->removeNewEmailRequest();
            $this->userManager()->updateUser($user);

            return $event->getResponse();
        }

        $form = $this->changeEmailFormFactory()->createForm();
        $form->setData($user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event = new FormEvent($form, $request);
            $this->eventDispatcher()->dispatch(VanioUserEvents::CHANGE_EMAIL_SUCCESS, $event);
            $user->setEmail($user->getNewEmail());
            $user->removeNewEmailRequest();
            $this->userManager()->updateUser($user);
            $response = $event->getResponse() ?: $this->createRedirectResponse($request);
            $event = new FilterUserResponseEvent($user, $request, $response);
            $this->eventDispatcher()->dispatch(VanioUserEvents::CHANGE_EMAIL_COMPLETED, $event);

            return $event->getResponse();
        }

        return $this->render('@VanioUser/ChangeEmail/confirm.html.twig', [
            'token' => $token,
            'form' => $form->createView(),
        ]);
    }

    private function createRedirectResponse(Request $request): RedirectResponse
    {
        return $this->httpUtils()->createRedirectResponse(
            $request,
            $this->getParameter('vanio_user.change_email.target_path')
        );
    }

    private function validateNewEmail(User $user): bool
    {
        $email = $user->getEmail();
        $user->setEmail($user->getNewEmail());
        $errors = $this->validator()->validate($user, null, ['Email']);
        $user->setEmail($email);

        return !count($errors);
    }

    private function userManager(): UserManagerInterface
    {
        return $this->get('fos_user.user_manager');
    }

    private function eventDispatcher(): EventDispatcherInterface
    {
        return $this->get('event_dispatcher');
    }

    private function changeEmailFormFactory(): FactoryInterface
    {
        return $this->get('vanio_user.form.change_email_form_factory');
    }

    private function httpUtils(): HttpUtils
    {
        return $this->get('vanio_user.security.http_utils');
    }

    private function validator(): ValidatorInterface
    {
        return $this->get('validator');
    }
}
