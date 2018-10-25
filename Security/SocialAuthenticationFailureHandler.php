<?php
namespace Vanio\UserBundle\Security;

use HWI\Bundle\OAuthBundle\Security\Core\Exception\AccountNotLinkedException;
use HWI\Bundle\OAuthBundle\Security\OAuthUtils;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;

class SocialAuthenticationFailureHandler extends DefaultAuthenticationFailureHandler
{
    /** @var DefaultAuthenticationFailureHandler */
    private $authenticationFailureHandler;

    /** @var HttpKernelInterface */
    protected $httpKernel;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var OAuthUtils */
    private $oAuthUtils;

    public function __construct(
        DefaultAuthenticationFailureHandler $authenticationFailureHandler,
        HttpKernelInterface $httpKernel,
        UrlGeneratorInterface $urlGenerator,
        TokenStorageInterface $tokenStorage,
        OAuthUtils $oAuthUtils
    ) {
        $this->authenticationFailureHandler = $authenticationFailureHandler;
        $this->httpKernel = $httpKernel;
        $this->urlGenerator = $urlGenerator;
        $this->tokenStorage = $tokenStorage;
        $this->oAuthUtils = $oAuthUtils;
    }

    /**
     * @return mixed[]
     */
    public function getOptions(): array
    {
        return $this->authenticationFailureHandler->getOptions();
    }

    public function setOptions(array $options): void
    {
        $this->authenticationFailureHandler->setOptions($options);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if (!$exception instanceof AccountNotLinkedException) {
            return $this->authenticationFailureHandler->onAuthenticationFailure($request, $exception);
        }

        $key = (string) Uuid::uuid4();
        $request->getSession()->set("_hwi_oauth.registration_error.{$key}", $exception);

        if ($request->getRequestFormat() === 'html') {
            return new RedirectResponse($this->urlGenerator->generate('hwi_oauth_connect_registration', [
                'key' => $key,
            ]));
        }

        $this->tokenStorage->setToken(new AnonymousToken('', 'anon.')); // is always null?
        $response = $this->httpKernel->handle(
            $request->duplicate([], [], ['_controller' => 'VanioUserBundle:Connect:registration', 'key' => $key]),
            HttpKernelInterface::SUB_REQUEST
        );

        return $response;
    }
}
