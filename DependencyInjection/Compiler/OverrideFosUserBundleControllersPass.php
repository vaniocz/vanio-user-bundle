<?php
namespace Vanio\UserBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Vanio\UserBundle\Controller\RegistrationController;
use Vanio\UserBundle\Controller\ResettingController;

class OverrideFosUserBundleControllersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('fos_user.registration.controller')) {
            $container->getDefinition('fos_user.registration.controller')->setClass(RegistrationController::class);
        }

        if ($container->hasDefinition('fos_user.resetting.controller')) {
            $container->getDefinition('fos_user.resetting.controller')->setClass(ResettingController::class);
        }
    }
}
