<?php
namespace Vanio\UserBundle\Security;

use FOS\UserBundle\Model\UserInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\FOSUBUserProvider as BaseFosubUserProvider;
use Symfony\Component\PropertyAccess\PropertyAccess;

class FosubUserProvider extends BaseFosubUserProvider
{
    /**
     * @throws \RuntimeException
     */
    public function disconnectService(UserInterface $user, string $service): bool
    {
        if (!$property = $this->properties[$service] ?? null) {
            throw new \RuntimeException(sprintf('No property for resource owner "%s".', $service));
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        if (!$propertyAccessor->getValue($user, $property)) {
            return false;
        }

        $propertyAccessor->setValue($user, $property, null);
        $this->userManager->updateUser($user);

        return true;
    }
}
