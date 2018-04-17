<?php
namespace Vanio\UserBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Vanio\UserBundle\Model\User;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class UserPassword extends Constraint
{
    /** @var string */
    public $message = 'This value should be the user\'s current password.';

    /** @var string */
    public $service = 'vanio_user.validator.user_password';

    /** @var User */
    public $user;

    public function validatedBy(): string
    {
        return $this->service;
    }

    /**
     * @return string[]
     */
    public function getRequiredOptions(): array
    {
        return ['user'];
    }
}
