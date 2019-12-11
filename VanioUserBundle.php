<?php
namespace Vanio\UserBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Vanio\UserBundle\DependencyInjection\Compiler\ConfigureOAuthUtilsPass;
use Vanio\UserBundle\DependencyInjection\Compiler\EmailConfirmationPass;
use Vanio\UserBundle\DependencyInjection\Compiler\EntryPointPass;
use Vanio\UserBundle\DependencyInjection\Compiler\FlashNotificationsPass;
use Vanio\UserBundle\DependencyInjection\Compiler\LogoutListenerPass;
use Vanio\UserBundle\DependencyInjection\Compiler\OverrideFosUserBundleControllersPass;
use Vanio\UserBundle\DependencyInjection\Compiler\DecorateAuthenticationHandlersPass;
use Vanio\UserBundle\DependencyInjection\Compiler\ValidationPass;

class VanioUserBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container
            ->addCompilerPass(new EmailConfirmationPass)
            ->addCompilerPass(new EntryPointPass)
            ->addCompilerPass(new FlashNotificationsPass)
            ->addCompilerPass(new OverrideFosUserBundleControllersPass)
            ->addCompilerPass(new DecorateAuthenticationHandlersPass)
            ->addCompilerPass(new ValidationPass)
            ->addCompilerPass(new ConfigureOAuthUtilsPass)
            ->addCompilerPass(new LogoutListenerPass);

        if (isset($container->getParameter('kernel.bundles')['DoctrineBundle'])) {
            $namespaces = [__DIR__ . '/Resources/config/doctrine-mapping' => 'Vanio\UserBundle\Model'];
            $container->addCompilerPass(DoctrineOrmMappingsPass::createXmlMappingDriver($namespaces));
        }
    }
}
