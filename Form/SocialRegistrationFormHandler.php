<?php
namespace Vanio\UserBundle\Form;

use HWI\Bundle\OAuthBundle\Form\FOSUBRegistrationFormHandler;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\User\UserInterface;

class SocialRegistrationFormHandler extends FOSUBRegistrationFormHandler
{
    protected function setUserInformation(UserInterface $user, UserResponseInterface $userInformation): UserInterface
    {
        $user = parent::setUserInformation($user, $userInformation);
        $accessor = PropertyAccess::createPropertyAccessor();

        if ($accessor->isWritable($user, 'name')) {
            $accessor->setValue($user, 'name', $userInformation->getRealName());
        }

        return $user;
    }
}
