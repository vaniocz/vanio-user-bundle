<?php
namespace Vanio\UserBundle\Controller;

use FOS\UserBundle\Controller\SecurityController as BaseSecurityController;
use Symfony\Component\HttpFoundation\Response;

class SecurityController extends BaseSecurityController
{
    use ResponseFormatTrait;

    public function apiClientAction(): Response
    {
        return $this->render('@VanioUser/Security/api_client.html.twig');
    }
}
