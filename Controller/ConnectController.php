<?php
namespace Vanio\UserBundle\Controller;

use HWI\Bundle\OAuthBundle\Controller\ConnectController as BaseConnectController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class ConnectController extends BaseConnectController
{
    public function connectAction(Request $request): RedirectResponse
    {
        $response = parent::connectAction($request);

        return $response instanceof RedirectResponse ? $response : $this->redirectToRoute('fos_user_security_login');
    }
}
