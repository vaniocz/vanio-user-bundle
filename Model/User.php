<?php
namespace Vanio\UserBundle\Model;

use FOS\UserBundle\Model\User as BaseUser;

abstract class User extends BaseUser implements \JsonSerializable
{
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    /** @var string|null */
    protected $newEmail;

    /** @var string|null */
    protected $newEmailConfirmationToken;

    /** @var \DateTime|null */
    protected $newEmailRequestedAt;

    public function isAdmin(): bool
    {
        return $this->isSuperAdmin() || $this->hasRole(static::ROLE_ADMIN);
    }

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

    public function removeNewEmailRequest(): void
    {
        $this->newEmail = null;
        $this->newEmailConfirmationToken = null;
        $this->newEmailRequestedAt = null;
    }

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        $data = [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'enabled' => $this->enabled,
        ];

        if (method_exists($this, 'getName')) {
            $data['name'] = $this->getName();
        }

        return $data;
    }
}
