<?php
namespace Vanio\UserBundle\Controller;

use FOS\UserBundle\Model\UserInterface;
use HWI\Bundle\OAuthBundle\Controller\ConnectController as BaseConnectController;
use HWI\Bundle\OAuthBundle\Event\FilterUserResponseEvent;
use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken;
use HWI\Bundle\OAuthBundle\Security\Core\Exception\AccountNotLinkedException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Vanio\UserBundle\Security\FosubUserProvider;
use Vanio\UserBundle\VanioUserEvents;
use Vanio\WebBundle\Request\RefererHelperTrait;

class ConnectController extends BaseConnectController
{
    use RefererHelperTrait;

    public function connectAction(Request $request): RedirectResponse
    {
        $response = parent::connectAction($request);

        return $response instanceof RedirectResponse ? $response : $this->redirectToRoute('fos_user_security_login');
    }

    /**
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     */
    public function connectionsAction(): Response
    {
        if (!$this->getParameter('hwi_oauth.connect')) {
            throw new NotFoundHttpException;
        } elseif (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw new AccessDeniedException;
        }

        return $this->render('VanioUserBundle:Connect:connections.html.twig', [
            'resourceOwnerProperties' => $this->getParameter('vanio_user.resource_owner_properties'),
        ]);
    }

    /**
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     */
    public function disconnectAction(Request $request, string $service): Response
    {
        if (!$this->getParameter('hwi_oauth.connect')) {
            throw new NotFoundHttpException;
        } elseif (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw new AccessDeniedException;
        }

        try {
            $response = $this->disconnect($request, $this->getUser(), $service);
        } catch (\RuntimeException $e) {
            throw new NotFoundHttpException;
        }

        return $response;
    }

    /**
     * @param Request $request
     * @return \Exception|string
     */
    protected function getErrorForRequest(Request $request)
    {
        $error = parent::getErrorForRequest($request);

        if ($error && !$error instanceof AccountNotLinkedException && $request->hasSession()) {
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $error);
        }

        return $error;
    }

    /**
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     */
    private function disconnect(Request $request, UserInterface $user, string $service): Response
    {
        $token = $this->getToken();
        $event = new FilterUserResponseEvent($user, $request, $this->redirectToReferer());

        if ($token instanceof OAuthToken && $token->getResourceOwnerName() === $service) {
            if (!$user->getPassword()) {
                throw new \RuntimeException('Cannot disconnect service.');
            }

            $token = new UsernamePasswordToken(
                $user,
                $user->getPassword(),
                $this->getParameter('vanio_user.firewall_name'),
                $user->getRoles()
            );
            $this->getTokenStorage()->setToken($token);
        }

        if ($this->getFosubUserProvider()->disconnectService($user, $service)) {
            $this->getEventDispatcher()->dispatch(VanioUserEvents::ACCOUNT_DISCONNECTED, $event);
        }

        return $event->getResponse();
    }

    private function getFosubUserProvider(): FosubUserProvider
    {
        return $this->get('hwi_oauth.user.provider.fosub_bridge');
    }

    private function getTokenStorage(): TokenStorageInterface
    {
        return $this->get('security.token_storage');
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->get('event_dispatcher');
    }
}
