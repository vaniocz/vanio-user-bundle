<?php
namespace Vanio\UserBundle\Tests\Fixtures;

use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;

class DummyUserManager implements UserManagerInterface
{
    public function createUser(): void
    {}

    public function deleteUser(UserInterface $user): void
    {}

    /**
     * @param mixed[] $criteria
     */
    public function findUserBy(array $criteria): void
    {}

    /**
     * @param mixed $username
     */
    public function findUserByUsername($username): void
    {}

    /**
     * @param mixed $email
     */
    public function findUserByEmail($email): void
    {}

    /**
     * @param mixed $usernameOrEmail
     */
    public function findUserByUsernameOrEmail($usernameOrEmail): void
    {}

    /**
     * @param mixed $token
     */
    public function findUserByConfirmationToken($token): void
    {}

    public function findUsers(): void
    {}

    public function getClass(): void
    {}

    public function reloadUser(UserInterface $user): void
    {}

    public function updateUser(UserInterface $user): void
    {}

    public function updateCanonicalFields(UserInterface $user): void
    {}

    public function updatePassword(UserInterface $user): void
    {}
}
