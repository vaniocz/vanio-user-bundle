<?php
namespace Vanio\UserBundle\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
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

        if ($this->targetPathResolver && $response instanceof RedirectResponse) {
            $this->passTargetPath($request, $response);
        }

        return $response;
    }

    public function setTargetPathResolver(TargetPathResolver $targetPathResolver)
    {
        $this->targetPathResolver = $targetPathResolver;
    }

    private function passTargetPath(Request $request, RedirectResponse $response)
    {
        if ($targetPath = $this->targetPathResolver->resolveTargetPathFromParameterValue($request)) {
            $targetUri = (new Uri($response->getTargetUrl()))->withAppendedQuery([
                $this->targetPathResolver->targetPathParameter() => $targetPath,
            ]);
            $response->setTargetUrl($targetUri->absoluteUri());
        }
    }
}
