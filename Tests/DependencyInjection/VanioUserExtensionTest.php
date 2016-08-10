<?php
namespace Vanio\UserBundle\Tests\DependencyInjection;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Vanio\UserBundle\Form\SocialRegistrationFormType;

class VanioUserExtensionTest extends KernelTestCase
{
    function test_default_configuration()
    {
        static::bootKernel();
        $config = static::$kernel->getContainer()->getParameter('vanio_user');

        $this->assertSame([
            'firewall_name' => 'main',
            'social_authentication' => false,
            'email_only' => false,
            'custom_storage_validation' => false,
            'use_flash_notifications' => true,
            'registration_target_path' => null,
            'pass_target_path' => [
                'enabled' => false,
                'default_target_path' => '/',
                'target_path_parameter' => '_target_path',
                'ignored_routes' => [],
                'ignored_route_prefixes' => [],
            ],
            'social_registration_form' => [
                'type' => SocialRegistrationFormType::class,
                'name' => 'hwi_oauth_registration_form',
                'validation_groups' => ['SocialRegistration'],
            ],
        ], $config);
    }
}
