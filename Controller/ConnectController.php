<?php
namespace Vanio\UserBundle\Controller;

use FOS\UserBundle\Model\UserInterface;
use HWI\Bundle\OAuthBundle\Controller\ConnectController as BaseConnectController;
use HWI\Bundle\OAuthBundle\Event\FilterUserResponseEvent;
use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken;
use HWI\Bundle\OAuthBundle\Security\Core\Exception\AccountNotLinkedException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Translation\TranslatorInterface;
use Vanio\UserBundle\Security\ApiClientTrustResolver;
use Vanio\UserBundle\Security\FosubUserProvider;
use Vanio\UserBundle\Security\OAuthUtils;
use Vanio\UserBundle\VanioUserEvents;
use Vanio\WebBundle\Request\RefererHelperTrait;
use Vanio\WebBundle\Serializer\Serializer;

class ConnectController extends BaseConnectController
{
    use ResponseFormatTrait;
    use RefererHelperTrait;

    /**
     * @param Request $request
     * @param string $key
     * @return Response
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function registrationAction(Request $request, $key): Response
    {
        if (!$this->routeExists('fos_user_registration_register')) {
            $error = $request->getSession()->get("_hwi_oauth.registration_error.{$key}");
            $parameters = $error instanceof AccountNotLinkedException
                ? ['%service%' => ucfirst($error->getResourceOwnerName())]
                : [];
            $message = $this->translator()->trans(
                'connect.social_account_not_connected',
                $parameters,
                'HWIOAuthBundle'
            );

            if ($request->getRequestFormat() === 'html') {
                $this->addFlash('danger', $message);
            } else {
                $data = [
                    'success' => false,
                    'errors' => [$message],
                ];

                return new Response($this->serializer()->serialize($data, $request->getRequestFormat()), 401);
            }

            return $this->redirectToReferer('fos_user_security_login');
        }

        return parent::registrationAction($request, $key);
    }

    /**
     * @param Request $request
     * @param string $service
     * @return Response
     */
    public function connectServiceAction(Request $request, $service): Response
    {
        $form = $request->request->get('form');

        if ($request->getRequestFormat() !== 'html') {
            $request->request->set('form', null);
        }

        $response = parent::connectServiceAction($request, $service);
        $request->request->set('form', $form);

        if ($request->getRequestFormat() !== 'html' && $response->isRedirection()) {
            throw new AccessDeniedException;
        }

        return $response;
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

        $oAuthUrl = $this->oAuthUtils()->getAuthorizationUrl($request, $service, $this->resolveRedirectUrl($request));

        if ($this->getParameter('hwi_oauth.connect') && $this->isGranted($this->getParameter('hwi_oauth.grant_rule'))) {
            $redirectUrl = $request->attributes->get('redirectUrl');
            $request->attributes->set('redirectUrl', null);
            $connectUrl = $this->oAuthUtils()->getServiceAuthUrl($request, $this->getResourceOwnerByName($service));
            $data = ['connectUrl' => $connectUrl];
            $request->attributes->set('redirectUrl', $redirectUrl);
        } else {
            $resourceOwnerCheckPath = $this->oAuthUtils()->getResourceOwnerCheckPath($service);
            $data = ['authenticationUrl' => $this->httpUtils()->generateUri($request, $resourceOwnerCheckPath)];
        }

        $data += ['oAuthUrl' => $oAuthUrl];

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

    private function disconnect(Request $request, UserInterface $user, string $service): Response
    {
        $token = $this->tokenStorage()->getToken();
        $response = $request->getRequestFormat() === 'html'
            ? $this->redirectToReferer('fos_user_profile_show')
            : new Response($this->serializer()->serialize(['success' => true], $request->getRequestFormat()));
        $event = new FilterUserResponseEvent($user, $request, $response);

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

    private function resolveRedirectUrl(Request $request): ?string
    {
        $redirectUrl = $request->get('redirectUrl');

        return $redirectUrl && $this->apiClientTrustResolver()->isTrustedApiClientUrl($redirectUrl)
            ? $redirectUrl
            : null;
    }

    private function translator(): TranslatorInterface
    {
        return $this->get('translator');
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

    private function apiClientTrustResolver(): ApiClientTrustResolver
    {
        return $this->get('vanio_user.security.api_client_trust_resolver');
    }

    private function oAuthUtils(): OAuthUtils
    {
        return $this->get('hwi_oauth.security.oauth_utils');
    }

    private function httpUtils(): HttpUtils
    {
        return $this->get('vanio_user.security.http_utils');
    }

    private function serializer(): Serializer
    {
        return $this->get('vanio_web.serializer.serializer');
    }
}
