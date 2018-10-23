<?php
namespace Vanio\UserBundle\Security;

use HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface;
use HWI\Bundle\OAuthBundle\Security\OAuthUtils as BaseOAuthUtils;
use Symfony\Component\HttpFoundation\Request;

class OAuthUtils extends BaseOAuthUtils
{
    /** @var ApiClientTrustResolver|null */
    private $apiClientTrustResolver;

    public function setApiClientTrustResolver(ApiClientTrustResolver $apiClientTrustResolver): void
    {
        $this->apiClientTrustResolver = $apiClientTrustResolver;
    }

    public function getServiceAuthUrl(Request $request, ResourceOwnerInterface $resourceOwner): string
    {
        if ($resourceOwner->getOption('auth_with_one_url')) {
            return $this->httpUtils->generateUri($request, $this->getResourceOwnerCheckPath($resourceOwner->getName()));
        } elseif ($redirectUrl = $request->get('redirectUrl')) {
            if ($this->apiClientTrustResolver && $this->apiClientTrustResolver->isTrustedApiClientUrl($redirectUrl)) {
                // This should be handled outside this class
                return $redirectUrl;
            }
        }

        $service = $request->attributes->get('service');
        $request->attributes->set('service', $resourceOwner->getName());
        $serviceAuthUrl = $this->httpUtils->generateUri($request, 'hwi_oauth_connect_service');
        $request->attributes->set('service', $service);

        return $serviceAuthUrl;
    }

    public function getResourceOwnerCheckPath($name): ?string
    {
        return parent::getResourceOwnerCheckPath($name);
    }
}
