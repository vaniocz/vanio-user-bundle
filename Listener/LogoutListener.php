<?php
namespace Vanio\UserBundle\Listener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Http\Firewall\LogoutListener as SymfonyLogoutListener;

class LogoutListener extends SymfonyLogoutListener
{
    /** @var bool */
    private $shouldCheckRequest = true;

    public function handleWithoutCheck(GetResponseEvent $event): void
    {
        $this->shouldCheckRequest = false;
        $this->handle($event);
        $this->shouldCheckRequest = true;
    }

    protected function requiresLogout(Request $request): bool
    {
        return $this->shouldCheckRequest ? parent::requiresLogout($request) : true;
    }
}
