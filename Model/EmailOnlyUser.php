<?php
namespace Vanio\UserBundle\Model;

abstract class EmailOnlyUser extends User
{
    /** @var string */
    protected $username = 'username'; // Fulfills username NotBlank constraint

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        unset($data['username']);

        return $data;
    }

    /**
     * @param string $email
     * @return $this
     */
    public function setEmail($email): self
    {
        parent::setEmail($email);
        parent::setUsername($email);

        return $this;
    }

    /**
     * @param string $emailCanonical
     * @return $this
     */
    public function setEmailCanonical($emailCanonical): self
    {
        parent::setEmailCanonical($emailCanonical);
        parent::setUsernameCanonical($emailCanonical);

        return $this;
    }

    /**
     * @internal
     * @param string $username
     * @return $this
     */
    public function setUsername($username): self
    {
        return $this->setEmail($username);
    }

    /**
     * @internal
     * @param string $usernameCanonical
     * @return $this
     */
    public function setUsernameCanonical($usernameCanonical): self
    {
        return $this->setEmailCanonical($usernameCanonical);
    }
}
