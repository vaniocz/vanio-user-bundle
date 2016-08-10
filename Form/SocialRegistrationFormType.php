<?php
namespace Vanio\UserBundle\Form;

use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SocialRegistrationFormType extends AbstractType
{
    /** @var bool */
    private $emailConfirmation;

    /** @var string|null */
    private $socialEmail;

    public function __construct(bool $emailConfirmation = false)
    {
        $this->emailConfirmation = $emailConfirmation;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->remove('plainPassword');

        if ($options['email_confirmation']) {
            $builder
                ->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData'])
                ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefault('email_confirmation', $this->emailConfirmation)
            ->setAllowedTypes('email_confirmation', 'bool');
    }

    public function getParent(): string
    {
        return RegistrationFormType::class;
    }

    public function onPreSetData(FormEvent $event)
    {
        /** @var UserInterface $user */
        if ($user = $event->getData()) {
            $this->socialEmail = $user->getEmail();
        }
    }

    /**
     * Accounts which use different email than the one provided by OAuth token need to be confirmed.
     *
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        /** @var UserInterface $user */
        if ($user = $event->getData()) {
            if ($user->getEmail() !== $this->socialEmail) {
                $user->setEnabled(false);
            }
        }
    }
}
