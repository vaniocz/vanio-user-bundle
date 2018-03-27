<?php
namespace Vanio\UserBundle\Model;

use FOS\UserBundle\Model\User as BaseUser;

abstract class User extends BaseUser
{
    const ROLE_ADMIN = 'ROLE_ADMIN';

    /** @var string */
    protected $newEmail;

    /** @var string */
    protected $newEmailConfirmationToken;

    /** @var \DateTime */
    protected $newEmailRequestedAt;

    public function getNewEmail(): ?string
    {
        return $this->newEmail;
    }

    public function getNewEmailConfirmationToken(): ?string
    {
        return $this->newEmailConfirmationToken;
    }

    public function requestNewEmail(string $email, string $confirmationToken): void
    {
        $this->newEmail = $email;
        $this->newEmailConfirmationToken = $confirmationToken;
        $this->newEmailRequestedAt = new \DateTime;
    }
}
