<?php
namespace Vanio\UserBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Vanio\Stdlib\Uri;

/**
 * Passes target path when redirecting upon failed authentication.
 */
class AuthenticationFailureHandler extends DefaultAuthenticationFailureHandler
{
    /** @var DefaultAuthenticationFailureHandler */
    private $authenticationFailureHandler;

    /** @var TargetPathResolver */
    private $targetPathResolver;

    public function __construct(
        DefaultAuthenticationFailureHandler $authenticationFailureHandler,
        TargetPathResolver $targetPathResolver
    ) {
        $this->authenticationFailureHandler = $authenticationFailureHandler;
        $this->targetPathResolver = $targetPathResolver;
    }

    public function getOptions(): array
    {
        return $this->authenticationFailureHandler->getOptions();
    }

    public function setOptions(array $options)
    {
        $this->authenticationFailureHandler->setOptions($options);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $response = $this->authenticationFailureHandler->onAuthenticationFailure($request, $exception);

        if ($response->isRedirection()) {
            if ($targetPath = $this->targetPathResolver->resolveTargetPathFromParameterValue($request)) {
                $targetUri = (new Uri($response->headers->get('Location')))->withAppendedQuery([
                    $this->targetPathResolver->targetPathParameter() => $targetPath,
                ]);
                $response->setTargetUrl($targetUri->absoluteUri());
            }
        }

        return $response;
    }
}
