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
    /** @var TargetPathResolver|null */
    private $targetPathResolver;

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $response = parent::onAuthenticationFailure($request, $exception);

        if ($this->targetPathResolver && $response->isRedirection()) {
            $this->passTargetPath($request, $response);
        }

        return $response;
    }

    public function setTargetPathResolver(TargetPathResolver $targetPathResolver): void
    {
        $this->targetPathResolver = $targetPathResolver;
    }

    private function passTargetPath(Request $request, Response $response): void
    {
        if ($targetPath = $this->targetPathResolver->resolveTargetPathFromParameterValue($request)) {
            $targetUri = (new Uri($response->headers->get('Location')))->withAppendedQuery([
                $this->targetPathResolver->targetPathParameter() => $targetPath,
            ]);
            $response->setTargetUrl($targetUri->absoluteUri());
        }
    }
}
