<?php
namespace Vanio\UserBundle\Templating;

use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Vanio\UserBundle\Security\TargetPathResolver;

class UserExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
{
    /** @var UserManagerInterface */
    private $userManager;

    /** @var TargetPathResolver */
    private $targetPathResolver;

    /** @var RequestStack */
    private $requestStack;

    /** @var CsrfTokenManagerInterface */
    private $tokenManager;

    /** @var array */
    private $config;

    public function __construct(
        UserManagerInterface $userManager,
        TargetPathResolver $targetPathResolver,
        RequestStack $requestStack,
        CsrfTokenManagerInterface $tokenManager,
        array $config
    ) {
        $this->userManager = $userManager;
        $this->targetPathResolver = $targetPathResolver;
        $this->requestStack = $requestStack;
        $this->tokenManager = $tokenManager;
        $this->config = $config;
    }

    /**
     * @return \Twig_SimpleFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction('csrf_token', [$this, 'getCsrfToken']),
            new \Twig_SimpleFunction('find_user', [$this, 'findUser']),
            new \Twig_SimpleFunction('target_path', [$this, 'resolveTargetPath']),
        ];
    }

    public function getGlobals(): array
    {
        return ['vanio_user' => $this->config];
    }

    public function getName(): string
    {
        return 'vanio_user_extension';
    }

    public function getCsrfToken(string $tokenId = 'authenticate'): string
    {
        return $this->tokenManager->getToken($tokenId);
    }

    /**
     * @param string $usernameOrEmail
     * @return UserInterface|null
     */
    public function findUser(string $usernameOrEmail)
    {
        return $this->userManager->findUserByUsernameOrEmail($usernameOrEmail);
    }

    /**
     * @return string|null
     */
    public function resolveTargetPath()
    {
        $request = $this->requestStack->getCurrentRequest();

        return $request ? $this->targetPathResolver->resolveTargetPath($request) : null;
    }
}
