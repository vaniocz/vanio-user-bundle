<?php
namespace Vanio\UserBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Vanio\UserBundle\Security\OAuthUtils;

class ConfigureOAuthUtilsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->getParameter('vanio_user.social_authentication')) {
            $container
                ->getDefinition('hwi_oauth.security.oauth_utils')
                ->setClass(OAuthUtils::class)
                ->addMethodCall('setApiClientTrustResolver', [
                    new Reference('vanio_user.security.api_client_trust_resolver'),
                ]);
        }
    }
}
