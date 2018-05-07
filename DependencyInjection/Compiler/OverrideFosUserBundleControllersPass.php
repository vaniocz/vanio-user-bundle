<?php
namespace Vanio\UserBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Vanio\UserBundle\Controller\ChangePasswordController;
use Vanio\UserBundle\Controller\GroupController;
use Vanio\UserBundle\Controller\ProfileController;
use Vanio\UserBundle\Controller\RegistrationController;
use Vanio\UserBundle\Controller\ResettingController;
use Vanio\UserBundle\Controller\SecurityController;

class OverrideFosUserBundleControllersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $controllers = [
            'fos_user.security.controller' => SecurityController::class,
            'fos_user.registration.controller' => RegistrationController::class,
            'fos_user.resetting.controller' => ResettingController::class,
            'fos_user.profile.controller' => ProfileController::class,
            'fos_user.change_password.controller' => ChangePasswordController::class,
            'fos_user.group.controller' => GroupController::class,
        ];

        foreach ($controllers as $id => $class) {
            if ($container->hasDefinition($id)) {
                $container->getDefinition($id)->setClass($class);
            }
        }
    }
}
