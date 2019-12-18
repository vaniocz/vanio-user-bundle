<?php
namespace Vanio\UserBundle\Controller;

use FOS\UserBundle\Controller\ProfileController as BaseProfileController;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends BaseProfileController
{
    use ResponseFormatTrait;

    public function editAction(Request $request): Response
    {
        $response = parent::editAction($request);

        if ($user = $this->getUser()) {
            $this->userManager()->reloadUser($user);
        }

        return $response;
    }

    private function userManager(): UserManagerInterface
    {
        return $this->get('fos_user.user_manager');
    }
}
