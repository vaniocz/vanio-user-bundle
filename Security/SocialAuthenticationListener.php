<?php
namespace Vanio\UserBundle\Security;

use HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface;
use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken;
use HWI\Bundle\OAuthBundle\Security\Http\ResourceOwnerMapInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener;

class SocialAuthenticationListener extends AbstractAuthenticationListener
{
    /** @var ResourceOwnerMapInterface */
    private $resourceOwnerMap;

    /** @var string[] */
    private $checkPaths;

    public function setResourceOwnerMap(ResourceOwnerMapInterface $resourceOwnerMap): void
    {
        $this->resourceOwnerMap = $resourceOwnerMap;
    }

    /**
     * @param string[] $checkPaths
     */
    public function setCheckPaths(array $checkPaths): void
    {
        $this->checkPaths = $checkPaths;
    }

    public function requiresAuthentication(Request $request): bool
    {
        foreach ($this->checkPaths as $checkPath) {
            if ($this->httpUtils->checkRequestPath($request, $checkPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return TokenInterface|RedirectResponse|null
     */
    protected function attemptAuthentication(Request $request)
    {
        /* @var ResourceOwnerInterface $resourceOwner */
        list($resourceOwner, $checkPath) = $this->resourceOwnerMap->getResourceOwnerByRequest($request);

        if (!$resourceOwner) {
            throw new AuthenticationException('No resource owner match the request.');
        } elseif (!$resourceOwner->handles($request)) {
            throw new AuthenticationException('No oauth code in the request.');
        }

        if ($request->query->has('authenticated') && $resourceOwner->getOption('auth_with_one_url')) {
            $request->attributes->set('service', $resourceOwner->getName());

            return new RedirectResponse(sprintf(
                '%s?code=%s&authenticated=true',
                $this->httpUtils->generateUri($request, 'hwi_oauth_connect_service'), $request->query->get('code')
            ));
        }

        $resourceOwner->isCsrfTokenValid($request->get('state'));

        if (!$redirectUrl = $request->get('redirectUrl')) {
            $redirectUrl = $this->httpUtils->createRequest($request, $checkPath)->getUri();
        }

        $accessToken = $resourceOwner->getAccessToken($request, $redirectUrl);
        $token = new OAuthToken($accessToken);
        $token->setResourceOwnerName($resourceOwner->getName());

        return $this->authenticationManager->authenticate($token);
    }
}
