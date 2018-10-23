<?php
namespace Vanio\UserBundle\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * Uses target path resolver when determining target path, ignores session.
 */
class AuthenticationSuccessHandler extends DefaultAuthenticationSuccessHandler
{
    /** @var HttpUtils */
    protected $httpUtils;

    /** @var DefaultAuthenticationSuccessHandler */
    private $authenticationSuccessHandler;

    /** @var TargetPathResolver */
    private $targetPathResolver;

    public function __construct(
        DefaultAuthenticationSuccessHandler $authenticationSuccessHandler,
        HttpUtils $httpUtils,
        TargetPathResolver $targetPathResolver
    ) {
        $this->authenticationSuccessHandler = $authenticationSuccessHandler;
        $this->httpUtils = $httpUtils;
        $this->targetPathResolver = $targetPathResolver;
    }

    public function getOptions(): array
    {
        return $this->authenticationSuccessHandler->getOptions();
    }

    public function setOptions(array $options)
    {
        $this->authenticationSuccessHandler->setOptions($options);
    }

    public function getProviderKey(): string
    {
        return $this->authenticationSuccessHandler->getProviderKey();
    }

    /**
     * @param string $providerKey
     */
    public function setProviderKey($providerKey)
    {
        $this->authenticationSuccessHandler->setProviderKey($providerKey);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        return $this->httpUtils->createRedirectResponse($request, $this->determineTargetUrl($request));
    }

    protected function determineTargetUrl(Request $request): string
    {
        $options = $this->authenticationSuccessHandler->getOptions();

        if ($options['always_use_default_target_path']) {
            return $this->options['default_target_path'];
        } elseif ($targetUrl = $this->targetPathResolver->resolveTargetPathFromParameterValue($request)) {
            return $targetUrl;
        }

        return $this->targetPathResolver->defaultTargetPath();
    }
}
