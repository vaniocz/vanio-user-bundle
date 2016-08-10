<?php
namespace Vanio\UserBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ValidationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $storage = $container->getParameter('vanio_user.db_driver');
        $container->setParameter('fos_user.storage', $storage);

        if ($storage === 'custom' || $container->getParameter('vanio_user.custom_storage_validation')) {
            return;
        }

        $pattern = '%s/../../Resources/config/storage-validation/%s/%s.xml';
        $paths = [
            sprintf($pattern, __DIR__, $storage, 'email'),
            sprintf($pattern, __DIR__, $storage, 'group'),
        ];

        if (!$container->getParameter('vanio_user.email_only')) {
            $paths[] = sprintf($pattern, __DIR__, $storage, 'username');
        }

        $container->getDefinition('validator.builder')->addMethodCall('addXmlMappings', [$paths]);
    }
}
