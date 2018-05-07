<?php
namespace Vanio\UserBundle\Form;

use FOS\UserBundle\Form\Factory\FactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class FormFactory implements FactoryInterface
{
    /** @var FactoryInterface */
    private $formFactory;

    /** @var RequestStack */
    private $requestStack;

    public function __construct(FactoryInterface $formFactory, RequestStack $requestStack)
    {
        $this->formFactory = $formFactory;
        $this->requestStack = $requestStack;
    }

    /**
     * @param mixed[] $options
     * @return FormInterface
     */
    public function createForm(array $options = []): FormInterface
    {
        return $this->formFactory->createForm($options + [
            'format' => $this->requestStack->getCurrentRequest()->getRequestFormat(),
        ]);
    }
}
