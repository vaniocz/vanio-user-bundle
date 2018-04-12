<?php
namespace Vanio\UserBundle\Tests\Fixtures;

use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;

class DummyUserManager implements UserManagerInterface
{
    public function createUser()
    {}

    public function deleteUser(UserInterface $user)
    {}

    /**
     * @param mixed[] $criteria
     */
    public function findUserBy(array $criteria)
    {}

    /**
     * @param mixed $username
     */
    public function findUserByUsername($username)
    {}

    /**
     * @param mixed $email
     */
    public function findUserByEmail($email)
    {}

    /**
     * @param mixed $usernameOrEmail
     */
    public function findUserByUsernameOrEmail($usernameOrEmail)
    {}

    /**
     * @param mixed $token
     */
    public function findUserByConfirmationToken($token)
    {}

    public function findUsers()
    {}

    public function getClass()
    {}

    public function reloadUser(UserInterface $user)
    {}

    public function updateUser(UserInterface $user)
    {}

    public function updateCanonicalFields(UserInterface $user)
    {}

    public function updatePassword(UserInterface $user)
    {}
}
