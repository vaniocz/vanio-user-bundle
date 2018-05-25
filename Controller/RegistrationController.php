<?php
namespace Vanio\UserBundle\Controller;

use FOS\UserBundle\Controller\RegistrationController as BaseRegistrationController;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseNullableUserEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Vanio\UserBundle\Security\LoginManager;
use Vanio\UserBundle\VanioUserEvents;
use Vanio\WebBundle\Request\RefererHelperTrait;

class RegistrationController extends BaseRegistrationController
{
    use ResponseFormatTrait;
    use RefererHelperTrait;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @param Request $request
     * @param string $token
     * @return Response
     */
    public function confirmAction(Request $request, $token): Response
    {
        try {
            return parent::confirmAction($request, $token);
        } catch (NotFoundHttpException $e) {
            $event = new GetResponseNullableUserEvent(null, $request);
            $this->eventDispatcher()->dispatch(FOSUserEvents::REGISTRATION_CONFIRM, $event);

            return $event->getResponse() ?: $this->redirectToReferer();
        }
    }

    public function sendConfirmationAction(Request $request, string $email): Response
    {
        $user = $this->userManager()->findUserByEmail($email);
        $event = new GetResponseNullableUserEvent($user, $request);
        $this->eventDispatcher()->dispatch(VanioUserEvents::REGISTRATION_CONFIRMATION_REQUEST, $event);

        if ($response = $event->getResponse()) {
            return $response;
        } elseif ($user) {
            if ($user->isEnabled()) {
                return $this->redirectToRoute('fos_user_security_login');
            }

            $event = new FormEvent($this->createForm(FormType::class, $user), $request);
            $this->eventDispatcher()->dispatch(VanioUserEvents::REGISTRATION_CONFIRMATION_REQUESTED, $event);

            if (!$response = $event->getResponse()) {
                throw new \LogicException(
                    'You need to enable email confirmation inside your "fos_user" configuration.'
                );
            }

            $this->userManager()->updateUser($user);

            return $response;
        }

        return $this->redirectToReferer();
    }

    public function unregisterAction(Request $request): Response
    {
        $user = $this->getUser();

        if (!$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        if ($request->isMethod('POST')) {
            $response = $this->loginManager()->logOutUser();

            if ($request->getSession()) {
                $request->getSession()->getFlashBag()->clear();
            }

            $this->userManager()->deleteUser($user);
            $event = new FilterUserResponseEvent($user, $request, $response ?: $this->redirect('/'));
            $this->eventDispatcher()->dispatch(VanioUserEvents::UNREGISTRATION_COMPLETED, $event);

            return $event->getResponse();
        }

        return $this->render('@VanioUser/Registration/unregister.html.twig');
    }

    private function eventDispatcher(): EventDispatcherInterface
    {
        return $this->get('event_dispatcher');
    }

    private function userManager(): UserManagerInterface
    {
        return $this->get('fos_user.user_manager');
    }

    private function loginManager(): LoginManager
    {
        return $this->get('fos_user.security.login_manager');
    }
}
