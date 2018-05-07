<?php
namespace Vanio\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Vanio\UserBundle\Validator\UserPassword;

class ChangeEmailFormType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param mixed[] $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }

    /**
     * @internal
     */
    public function onPreSetData(FormEvent $event)
    {
        if (!$user = $event->getData()) {
            return;
        }

        $groups = $event->getForm()->getConfig()->getOption('validation_groups');
        $event->getForm()->add('password', PasswordType::class, [
            'label' => 'form.password',
            'translation_domain' => 'FOSUserBundle',
            'mapped' => false,
            'constraints' => new UserPassword([
                'message' => 'fos_user.current_password.invalid',
                'user' => $user,
                'groups' => is_array($groups) && $groups ? [reset($groups)] : null,
            ]),
        ]);
    }
}
