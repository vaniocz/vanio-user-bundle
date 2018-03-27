<?php
namespace Vanio\UserBundle\DependencyInjection;

use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Vanio\UserBundle\Form\ChangePasswordFormType;
use Vanio\UserBundle\Form\ProfileFormType;
use Vanio\UserBundle\Form\RegistrationFormType;

class VanioUserExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration, $configs);
        $loader = new XmlFileLoader($container, new FileLocator(sprintf('%s/../Resources/config', __DIR__)));
        $loader->load('config.xml');
        $container->setParameter('vanio_user', $config);
        $container->setParameter('vanio_user.db_driver', $container->getParameter('fos_user.storage'));
        $container->setParameter('fos_user.storage', 'custom');

        foreach ($config as $key => $value) {
            $container->setParameter("vanio_user.$key", $value);

            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $container->setParameter("vanio_user.$key.$k", $v);
                }
            }
        }

        if ($config['use_flash_notifications']) {
            $loader->load('flash_notifications.xml');
        }

        if ($config['social_authentication']) {
            $loader->load('social_authentication.xml');
        }

        if ($config['registration_target_path']) {
            $container
                ->getDefinition('vanio_user.security.redirect_on_registration_success')
                ->setAbstract(false)
                ->addTag('kernel.event_subscriber');
        }

        if ($config['new_email']['confirmation']['enabled']) {
            $this->loadNewEmail($config['new_email'], $container, $loader, $config['from_email']);
        }
    }

    public function prepend(ContainerBuilder $container)
    {
        $config = $this->processExtensionConfig($container, 'vanio_user');
        $this->prependSecurityConfig($container, $config);
        $bundles = $container->getParameter('kernel.bundles');
        $config['firewall_name'] = $config['firewall_name'] ?? $this->autodetectFirewallName($container);
        $config['social_authentication'] = $config['social_authentication'] ?? isset($bundles['HWIOAuthBundle']);
        $this->prependFosUserConfig($container, $config);
        $container->prependExtensionConfig('vanio_user', [
            'firewall_name' => $config['firewall_name'],
            'social_authentication' => $config['social_authentication'],
        ]);

        if (!$container->hasExtension('hwi_oauth')) {
            return;
        }

        $container->prependExtensionConfig('hwi_oauth', ['firewall_names' => [$config['firewall_name']]]);
        $container->setParameter(
            'vanio_user.resource_owner_properties',
            $this->processExtensionConfig($container, 'hwi_oauth')['fosub']['properties'] ?? []
        );
        $container->prependExtensionConfig('twig', [
            'paths' => [sprintf('%s/../Resources/views', __DIR__) => 'HWIOAuth'],
        ]);
    }

    private function prependSecurityConfig(ContainerBuilder $container, array $config)
    {
        $container->prependExtensionConfig('security', [
            'encoders' => [UserInterface::class => 'bcrypt'],
            'providers' => [
                'fos_userbundle' => [
                    'id' => $config['email_only']
                        ? 'fos_user.user_provider.username_email'
                        : 'fos_user.user_provider.username',
                ],
            ],
        ]);
    }

    private function prependFosUserConfig(ContainerBuilder $container, array $config)
    {
        $container->prependExtensionConfig('fos_user', [
            'firewall_name' => $config['firewall_name'],
            'use_listener' => false,
            'use_flash_notifications' => $config['use_flash_notifications'],
            'registration' => [
                'form' => ['type' => RegistrationFormType::class],
                'confirmation' => ['template' => 'VanioUserBundle:Registration:email.html.twig'],
            ],
            'resetting' => [
                'email' => ['template' => 'VanioUserBundle:Resetting:email.html.twig'],
            ],
            'profile' => [
                'form' => ['type' => ProfileFormType::class],
            ],
            'change_password' => [
                'form' => ['type' => ChangePasswordFormType::class],
            ],
            'service' => ['mailer' => 'vanio_user.mailer.twig_swift'],
        ]);
    }

    private function loadNewEmail(array $config, ContainerBuilder $container, XmlFileLoader $loader, array $fromEmail)
    {
        $loader->load('new_email_confirmation.xml');

        if (isset($config['confirmation']['from_email'])) {
            // overwrite the global one
            $fromEmail = $config['confirmation']['from_email'];
            unset($config['confirmation']['from_email']);
        }
        $container->setParameter('vanio_user.new_email.confirmation.from_email', [$fromEmail['address'] => $fromEmail['sender_name']]);

        $this->remapParametersNamespaces($config, $container, array(
            'confirmation' => 'fos_user.registration.confirmation.%s',
        //    'form' => 'fos_user.registration.form.%s',
        ));
    }

    protected function remapParameters(array $config, ContainerBuilder $container, array $map)
    {
        foreach ($map as $name => $paramName) {
            if (array_key_exists($name, $config)) {
                $container->setParameter($paramName, $config[$name]);
            }
        }
    }

    protected function remapParametersNamespaces(array $config, ContainerBuilder $container, array $namespaces)
    {
        foreach ($namespaces as $ns => $map) {
            if ($ns) {
                if (!array_key_exists($ns, $config)) {
                    continue;
                }
                $namespaceConfig = $config[$ns];
            } else {
                $namespaceConfig = $config;
            }
            if (is_array($map)) {
                $this->remapParameters($namespaceConfig, $container, $map);
            } else {
                foreach ($namespaceConfig as $name => $value) {
                    $container->setParameter(sprintf($map, $name), $value);
                }
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @return string
     * @throws \LogicException
     */
    private function autodetectFirewallName(ContainerBuilder $container): string
    {
        $securityConfig = $this->processExtensionConfig($container, 'security');

        foreach ($securityConfig['firewalls'] as $name => $options) {
            if ($options['security']) {
                return $name;
            }
        }

        throw new \LogicException('Unable to autodetect firewall name. Have you properly configured security extension?');
    }

    private function processExtensionConfig(ContainerBuilder $container, string $name): array
    {
        /** @var Extension $extension */
        $extension = $container->getExtension($name);

        return $this->processConfiguration(
            $extension->getConfiguration([], $container),
            $container->getExtensionConfig($name)
        );
    }
}
