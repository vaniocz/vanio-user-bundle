<?php
namespace Vanio\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Vanio\UserBundle\Validator\UserPassword;

class ChangeEmailFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $constraintsOptions = [
            'message' => 'fos_user.current_password.invalid',
            'user' => $builder->getData(),
        ];

        if (is_array($options['validation_groups']) && $options['validation_groups']) {
            $constraintsOptions['groups'] = [reset($options['validation_groups'])];
        }

        $builder->add('password', PasswordType::class, [
            'label' => 'form.password',
            'translation_domain' => 'FOSUserBundle',
            'mapped' => false,
            'constraints' => new UserPassword($constraintsOptions),
        ]);
    }
}
