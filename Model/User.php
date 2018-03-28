<?php
namespace Vanio\UserBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;

/**
 * @ORM\MappedSuperclass
 */
abstract class User extends BaseUser
{
    const ROLE_ADMIN = 'ROLE_ADMIN';

    /**
     * @var string|null
     * @ORM\Column
     */
    protected $newEmail;

    /**
     * @var string|null
     * @ORM\Column
     */
    protected $newEmailConfirmationToken;

    /**
     * @var \DateTime|null
     * @ORM\Column
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
}
