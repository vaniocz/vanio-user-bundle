<?php
namespace Vanio\UserBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Exception\MissingOptionsException;
use Vanio\UserBundle\Model\User;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class UserPassword extends Constraint
{
    public $message = 'This value should be the user\'s current password.';
    public $service = 'vanio_user.validator.user_password';

    /** @var User */
    public $user;

    public function __construct($options = null)
    {
        parent::__construct($options);
    }

    public function validatedBy()
    {
        return $this->service;
    }

    public function getRequiredOptions()
    {
        return ['user'];
    }
}
