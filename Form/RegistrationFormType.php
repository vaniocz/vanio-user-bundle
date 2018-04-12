<?php
namespace Vanio\UserBundle\Form;

use FOS\UserBundle\Form\Type\RegistrationFormType as BaseRegistrationFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegistrationFormType extends AbstractType
{
    /** @var bool */
    private $emailOnly;

    public function __construct(bool $emailOnly = false)
    {
        $this->emailOnly = $emailOnly;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param mixed[] $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['email_only']) {
            $builder->remove('username');
        }

        if (method_exists($options['data_class'], 'setName')) {
            $builder->add('name', null, [
                'label' => 'form.name',
                'translation_domain' => 'FOSUserBundle',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefault('email_only', $this->emailOnly)
            ->setAllowedTypes('email_only', 'bool');
    }

    public function getParent(): string
    {
        return BaseRegistrationFormType::class;
    }
}
