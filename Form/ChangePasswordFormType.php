<?php
namespace Vanio\UserBundle\Form;

use FOS\UserBundle\Form\Type\ChangePasswordFormType as BaseChangePasswordFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\User\UserInterface;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }

    public function getParent(): string
    {
        return BaseChangePasswordFormType::class;
    }

    public function onPreSetData(FormEvent $event)
    {
        /** @var UserInterface|null $user */
        $user = $event->getData();

        if ($user && !$user->getPassword()) {
            $event->getForm()->remove('current_password');
        }
    }
}
