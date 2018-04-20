<?php
namespace Vanio\UserBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Vanio\UserBundle\DependencyInjection\Compiler\EmailConfirmationPass;
use Vanio\UserBundle\DependencyInjection\Compiler\EntryPointPass;
use Vanio\UserBundle\DependencyInjection\Compiler\FlashNotificationsPass;
use Vanio\UserBundle\DependencyInjection\Compiler\OverrideFosUserBundleControllersPass;
use Vanio\UserBundle\DependencyInjection\Compiler\PassTargetPathPass;
use Vanio\UserBundle\DependencyInjection\Compiler\ValidationPass;

class VanioUserBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container
            ->addCompilerPass(new EmailConfirmationPass)
            ->addCompilerPass(new EntryPointPass)
            ->addCompilerPass(new FlashNotificationsPass)
            ->addCompilerPass(new OverrideFosUserBundleControllersPass)
            ->addCompilerPass(new PassTargetPathPass)
            ->addCompilerPass(new ValidationPass);

        if (isset($container->getParameter('kernel.bundles')['DoctrineBundle'])) {
            $namespaces = [__DIR__ . '/Resources/config/doctrine-mapping' => 'Vanio\UserBundle\Model'];
            $container->addCompilerPass(DoctrineOrmMappingsPass::createXmlMappingDriver($namespaces));
        }
    }

    /**
     * @return string|null
     */
    public function getParent()
    {
        return 'FOSUserBundle';
    }
}
