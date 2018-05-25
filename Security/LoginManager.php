<?php
namespace Vanio\UserBundle\Security;

use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Security\LoginManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Firewall\LogoutListener;
use Symfony\Component\Security\Http\FirewallMapInterface;

class LoginManager implements LoginManagerInterface
{
    /** @var LoginManagerInterface */
    private $loginManager;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var HttpKernelInterface */
    private $httpKernel;

    /** @var FirewallMapInterface */
    private $firewallMap;

    /** @var RequestStack */
    private $requestStack;

    /** @var string */
    private $firewallName;

    public function __construct(
        LoginManagerInterface $loginManager,
        TokenStorageInterface $tokenStorage,
        HttpKernelInterface $httpKernel,
        FirewallMapInterface $firewallMap,
        RequestStack $requestStack,
        string $firewallName = 'main'
    ) {
        $this->loginManager = $loginManager;
        $this->tokenStorage = $tokenStorage;
        $this->httpKernel = $httpKernel;
        $this->firewallMap = $firewallMap;
        $this->requestStack = $requestStack;
        $this->firewallName = $firewallName;
    }

    /**
     * @param string|null $firewallName
     * @param UserInterface $user
     * @param Response|null $response
     * @return void
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function logInUser($firewallName, UserInterface $user, ?Response $response = null): void
    {
        $this->loginManager->logInUser($firewallName ?? $this->firewallName, $user, $response);
    }

    public function logOutUser(): ?Response
    {
        if ($request = $this->requestStack->getCurrentRequest()) {
            foreach ($this->firewallMap->getListeners($request) as $listener) {
                if ($listener instanceof LogoutListener) {
                    $logoutListener = $listener;
                    break;
                }
            }
        }

        if (isset($logoutListener)) {
            $requestType = $this->requestStack->getParentRequest()
                ? HttpKernelInterface::SUB_REQUEST
                : HttpKernelInterface::MASTER_REQUEST;
            $event = new GetResponseEvent($this->httpKernel, $request, $requestType);
            $logoutListener->handle($event);

            return $event->getResponse();
        }

        $this->tokenStorage->setToken(null);

        return null;
    }
}
