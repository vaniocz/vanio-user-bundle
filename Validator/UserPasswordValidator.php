<?php
namespace Vanio\UserBundle\Validator;

use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UserPasswordValidator extends ConstraintValidator
{
    /** @var EncoderFactoryInterface */
    private $encoderFactory;

    public function __construct(EncoderFactoryInterface $encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    /**
     * @param mixed $password
     * @param Constraint $constraint
     */
    public function validate($password, Constraint $constraint)
    {
        if (!$constraint instanceof UserPassword) {
            throw new UnexpectedTypeException($constraint, sprintf('%s\UserPassword', __NAMESPACE__));
        } elseif ($password === null || $password === '') {
            $this->context->addViolation($constraint->message);

            return;
        }

        $encoder = $this->encoderFactory->getEncoder($constraint->user);

        if (!$encoder->isPasswordValid($constraint->user->getPassword(), $password, $constraint->user->getSalt())) {
            $this->context->addViolation($constraint->message);
        }
    }
}
