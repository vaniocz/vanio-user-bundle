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

    public function findUserBy(array $criteria)
    {}

    public function findUserByUsername($username)
    {}

    public function findUserByEmail($email)
    {}

    public function findUserByUsernameOrEmail($usernameOrEmail)
    {}

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
