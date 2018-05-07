<?php
namespace Vanio\UserBundle\Model;

use FOS\UserBundle\Model\User as BaseUser;

abstract class User extends BaseUser implements \JsonSerializable
{
    const ROLE_ADMIN = 'ROLE_ADMIN';

    /**
     * @var string|null
     */
    protected $newEmail;

    /**
     * @var string|null
     */
    protected $newEmailConfirmationToken;

    /**
     * @var \DateTime|null
     */
    protected $newEmailRequestedAt;

    /**
     * @return string|null
     */
    public function getNewEmail()
    {
        return $this->newEmail;
    }

    /**
     * @return string|null
     */
    public function getNewEmailConfirmationToken()
    {
        return $this->newEmailConfirmationToken;
    }

    public function requestNewEmail(string $email, string $confirmationToken)
    {
        $this->newEmail = $email;
        $this->newEmailConfirmationToken = $confirmationToken;
        $this->newEmailRequestedAt = new \DateTime;
    }

    public function removeNewEmailRequest()
    {
        $this->newEmail = null;
        $this->newEmailConfirmationToken = null;
        $this->newEmailRequestedAt = null;
    }

    public function jsonSerialize(): array
    {
        $data = [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
        ];

        if (method_exists($this, 'getName')) {
            $data['name'] = $this->getName();
        }

        return $data;
    }
}
