# [<img alt="Vanio" src="http://www.vanio.cz/img/vanio-logo.png" width="130" align="top">](http://www.vanio.cz) User Bundle

[![Build Status](https://travis-ci.org/vaniocz/vanio-user-bundle.svg?branch=master)](https://travis-ci.org/vaniocz/vanio-user-bundle)
[![Coverage Status](https://coveralls.io/repos/github/vaniocz/vanio-user-bundle/badge.svg?branch=master)](https://coveralls.io/github/vaniocz/vanio-user-bundle?branch=master)
![PHP7](https://img.shields.io/badge/php-7-6B7EB9.svg)
[![License](https://poser.pugx.org/vanio/vanio-user-bundle/license)](https://github.com/vaniocz/vanio-user-bundle/blob/master/LICENSE)

A Symfony2 Bundle integrating FOSUserBundle and HWIOauthBundle with some additional features and sane defaults.

# Installation
Installation can be done as usually using composer.
`composer require vanio/vanio-user-bundle`

You can also install HWIOAuthBundle optionally if you want to support authentication via social accounts.
`composer require hwi/oauth-bundle`

Next step is to register this bundle as well as bundles it depends on inside your `AppKernel`.
```php
// app/AppKernel.php
// ...

class AppKernel extends Kernel
{
    // ...

    public function registerBundles(): array
    {
        $bundles = [
            // ...
            new FOS\UserBundle\FOSUserBundle,
            new HWI\Bundle\OAuthBundle\HWIOAuthBundle, // Optional
            new Vanio\UserBundle\VanioUserBundle,
            new Vanio\UserBundle\VanioWebBundle,
        ];

        // ...
    }
}
```

# Default Configuration
```yml
firewall_name: ~ # firewall name, auto-detected from security configuration when empty
email_only: false # whether to completely omit username and use email only
custom_storage_validation: false # whether to disable predefined uniqueness validation
use_flash_notifications: true # whether to enable notifications using flash messages (notify also on login and logout as an addition to FOSUserBundle)
registration_target_path: ~ # target path used for redirection after completed registration instead of default static pages
pass_target_path: # whether to pass referer in URL query parameter and use it as target path
    enabled: false
    default_target_path: / # default value when target path is not present, default_target_path option inside security configuration is ignored
    target_path_parameter: _target_path: # name of the parameter
    ignored_routes: [] # route names to ignore
    ignored_route_prefixes: # route name prefixes to ignore, the default ones are always merged in
        - fos_user_security_
        - fos_user_registration_
        - fos_user_resetting_
        - hwi_oauth_

social_authentication: ~ # whether to enable social authentication, automatically enabled when HWIOAuthUserBundle is installed
social_registration_form: # social registration form configuration
    type: Vanio\UserBundle\Form\SocialRegistrationFormType # form type
    name: hwi_oauth_registration_form # form name
    validation_groups: [SocialRegistration] # form validation groups
```

All these values are available as container parameters. They are prefixed using `vanio_user.` prefix.  
This bundle prepends some defaults of SecurityBundle, FOSUserBundle and HWIOAuthBundle based on these configuration values to make the configuration easier.

The default prepended values are:

```yml
security:
    encoders:
        FOS\UserBundle\Model: bcrypt
    providers:
        fos_userbundle:
            id: fos_user.user_provider.username # or fos_user.user_provider.username_email when %vanio_user.email_only%

fos_user:
    firewall_name: %vanio_user.firewall_name%
    use_listener: false
    use_flash_notifications: %vanio_user.use_flash_notifications%
    registration:
        form:
            type: FOS\UserBundle\Form\Type\RegistrationFormType # or Vanio\UserBundle\Form\EmailOnlyRegistration when %vanio_user.email_only%
        confirmation:
            template: VanioUserBundle:Registration:email.html.twig
    resetting:
        email:
            template: VanioUserBundle:Resetting:email.html.twig
    profile:
        form:
            type: FOS\UserBundle\Form\Type\ProfileFormType # or Vanio\UserBundle\Form\EmailOnlyProfileType when %vanio_user.email_only%
    service:
        mailer: fos_user.mailer.twig_swift

hwi_oauth:
    firewall_names: [%vanio_user.firewall_name%]
```
