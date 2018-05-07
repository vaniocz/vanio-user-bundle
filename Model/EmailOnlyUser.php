<?php
namespace Vanio\UserBundle\Model;

abstract class EmailOnlyUser extends User
{
    /** @var string */
    protected $username = 'username'; // Fulfills username NotBlank constraint

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        unset($data['username']);

        return $data;
    }

    /**
     * @param string $email
     * @return $this
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function setEmail($email): self
    {
        parent::setEmail($email);
        parent::setUsername($email ?? 'username');

        return $this;
    }

    /**
     * @param string $emailCanonical
     * @return $this
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
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
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function setUsername($username): self
    {
        return $this->setEmail($username);
    }

    /**
     * @internal
     * @param string $usernameCanonical
     * @return $this
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function setUsernameCanonical($usernameCanonical): self
    {
        return $this->setEmailCanonical($usernameCanonical);
    }
}
