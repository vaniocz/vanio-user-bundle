<?php
namespace Vanio\UserBundle\Model;

use FOS\UserBundle\Model\User as BaseUser;

abstract class User extends BaseUser
{
    const ROLE_ADMIN = 'ROLE_ADMIN';
}
