<?php
namespace Vanio\UserBundle\Form;

use FOS\UserBundle\Form\Type\ResettingFormType as BaseResettingFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResettingFormType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param mixed[] $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
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
            ->setDefault('format', 'html')
            ->setAllowedTypes('format', 'string');
    }

    public function getParent(): string
    {
        return BaseResettingFormType::class;
    }
}
