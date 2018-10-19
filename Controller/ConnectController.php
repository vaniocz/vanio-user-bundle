<?php
namespace Vanio\UserBundle\Controller;

use FOS\UserBundle\Model\UserInterface;
use HWI\Bundle\OAuthBundle\Controller\ConnectController as BaseConnectController;
use HWI\Bundle\OAuthBundle\Event\FilterUserResponseEvent;
use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken;
use HWI\Bundle\OAuthBundle\Security\Core\Exception\AccountNotLinkedException;
use HWI\Bundle\OAuthBundle\Security\OAuthUtils;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Vanio\UserBundle\Security\FosubUserProvider;
use Vanio\UserBundle\VanioUserEvents;
use Vanio\WebBundle\Request\RefererHelperTrait;
use Vanio\WebBundle\Serializer\Serializer;
use Vanio\WebBundle\Translation\FlashMessage;

class ConnectController extends BaseConnectController
{
    use RefererHelperTrait;

    public function connectAction(Request $request): Response
    {
        $response = parent::connectAction($request);

        return $response->isRedirection() ? $response : $this->redirectToRoute('fos_user_security_login');
    }

    /**
     * @param Request $request
     * @param string $key
     * @return Response
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function registrationAction(Request $request, $key): Response
    {
        if (!$this->routeExists('fos_user_registration_register')) {
            $error = $request->getSession()->get("_hwi_oauth.registration_error.$key");
            $parameters = $error instanceof AccountNotLinkedException
                ? ['%service%' => ucfirst($error->getResourceOwnerName())]
                : [];
            $this->addFlashMessage(FlashMessage::TYPE_DANGER, 'connect.social_account_not_connected', $parameters);

            return $this->redirectToReferer('fos_user_security_login');
        }

        return parent::registrationAction($request, $key);
    }

    public function connectionsAction(): Response
    {
        if (!$this->getParameter('hwi_oauth.connect') || !$this->routeExists('fos_user_profile_show')) {
            throw new NotFoundHttpException;
        } elseif (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw new AccessDeniedException;
        }

        return $this->render('@VanioUser/Connect/connections.html.twig', [
            'resourceOwnerProperties' => $this->getParameter('vanio_user.resource_owner_properties'),
        ]);
    }

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
     * @param string $service
     * @return Response
     */
    public function redirectToServiceAction(Request $request, $service): Response
    {
        if ($request->getRequestFormat() === 'html') {
            return parent::redirectToServiceAction($request, $service);
        }

        try {
            $authorizationUrl = $this->oAuthUtils()->getAuthorizationUrl(
                $request,
                $service,
                $request->get('redirectUrl')
            );
        } catch (\RuntimeException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        $data = [
            'success' => true,
            'authorizationUrl' => $authorizationUrl,
            'redirectUrl' => $this->oAuthUtils()->getServiceAuthUrl($request, $this->getResourceOwnerByName($service)),
        ];

        return new Response($this->serializer()->serialize($data, $request->getRequestFormat()));
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
     * @param string $view
     * @param mixed[] $parameters
     * @param Response|null $response
     * @return Response
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    protected function render($view, array $parameters = [], ?Response $response = null): Response
    {
        if (preg_match('~HWIOAuthBundle::?(.*\.html\.twig)$~', $view, $matches)) {
            $view = sprintf('@HWIOAuth/%s', strtr($matches[1], ':', '/'));
        }

        return parent::render($view, $parameters, $response);
    }

    private function disconnect(Request $request, UserInterface $user, string $service): Response
    {
        $token = $this->tokenStorage()->getToken();
        $event = new FilterUserResponseEvent($user, $request, $this->redirectToReferer('fos_user_profile_show'));

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
            $this->tokenStorage()->setToken($token);
        }

        if ($this->fosubUserProvider()->disconnectService($user, $service)) {
            $this->eventDispatcher()->dispatch(VanioUserEvents::ACCOUNT_DISCONNECTED, $event);
        }

        return $event->getResponse();
    }

    private function routeExists(string $route): bool
    {
        try {
            $this->generateUrl($route);
        } catch (RouteNotFoundException $e) {
            return false;
        }

        return true;
    }

    /**
     * @param string $type
     * @param string $message
     * @param mixed[] $parameters
     */
    private function addFlashMessage(string $type, string $message, array $parameters = []): void
    {
        $this->addFlash($type, new FlashMessage($message, $parameters, 'HWIOAuthBundle'));
    }

    private function fosubUserProvider(): FosubUserProvider
    {
        return $this->get('hwi_oauth.user.provider.fosub_bridge');
    }

    private function tokenStorage(): TokenStorageInterface
    {
        return $this->get('security.token_storage');
    }

    private function eventDispatcher(): EventDispatcherInterface
    {
        return $this->get('event_dispatcher');
    }

    private function oAuthUtils(): OAuthUtils
    {
        return $this->get('hwi_oauth.security.oauth_utils');
    }

    private function serializer(): Serializer
    {
        return $this->get('vanio_web.serializer.serializer');
    }
}
