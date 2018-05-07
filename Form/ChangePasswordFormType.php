<?php
namespace Vanio\UserBundle\Form;

use FOS\UserBundle\Form\Type\ChangePasswordFormType as BaseChangePasswordFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

class ChangePasswordFormType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param mixed[] $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['format'] !== 'html') {
            $builder
                ->remove('current_password')
                ->remove('plainPassword')
                ->add('currentPassword', PasswordType::class, [
                    'mapped' => false,
                    'translation_domain' => 'FOSUserBundle',
                    'constraints' => new UserPassword([
                        'message' => 'fos_user.current_password.invalid',
                        'groups' => is_array($options['validation_groups']) && $options['validation_groups']
                            ? reset($options['validation_groups'])
                            : null,
                    ]),
                ])
                ->add('newPassword', PasswordType::class, [
                    'property_path' => 'plainPassword',
                    'translation_domain' => 'FOSUserBundle',
                ]);
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }

    public function getParent(): string
    {
        return BaseChangePasswordFormType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('format', 'html')
            ->setAllowedTypes('format', 'string');
    }

    /**
     * @internal
     */
    public function onPreSetData(FormEvent $event): void
    {
        /** @var UserInterface|null $user */
        $user = $event->getData();

        if ($user && !$user->getPassword()) {
            $event->getForm()->remove('current_password');
        }
    }
}
