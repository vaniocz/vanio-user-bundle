<?php
namespace Vanio\UserBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Vanio\WebBundle\Translation\FlashMessage;

/**
 * Notifies upon successful logout and removes target path from session.
 */
class NotifyingLogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    use TargetPathTrait;

    /** @var LogoutSuccessHandlerInterface */
    private $logoutSuccessHandler;

    /** @var string */
    private $providerKey;

    public function __construct(LogoutSuccessHandlerInterface $logoutSuccessHandler, string $providerKey)
    {
        $this->logoutSuccessHandler = $logoutSuccessHandler;
        $this->providerKey = $providerKey;
    }

    public function onLogoutSuccess(Request $request): Response
    {
        $response = $this->logoutSuccessHandler->onLogoutSuccess($request);
        $session = $request->getSession();

        if ($session instanceof Session) {
            $flashMessage = FlashMessage::success('security.flash.logged_out', [], 'FOSUserBundle');
            $session->getFlashBag()->add(FlashMessage::TYPE_SUCCESS, $flashMessage);
            $this->removeTargetPath($session, $this->providerKey);
        }

        return $response;
    }
}
