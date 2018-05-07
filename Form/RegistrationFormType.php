<?php
namespace Vanio\UserBundle\Form;

use FOS\UserBundle\Form\Type\RegistrationFormType as BaseRegistrationFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
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
    public function buildForm(FormBuilderInterface $builder, array $options): void
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

        if ($options['format'] !== 'html') {
            $builder
                ->remove('plainPassword')
                ->add('password', PasswordType::class, [
                    'property_path' => 'plainPassword',
                    'translation_domain' => 'FOSUserBundle',
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'email_only' => $this->emailOnly,
                'format' => 'html',
            ])
            ->setAllowedTypes('email_only', 'bool')
            ->setAllowedTypes('format', 'string');
    }

    public function getParent(): string
    {
        return BaseRegistrationFormType::class;
    }
}
