<?php
namespace Vanio\UserBundle\Templating;

use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Vanio\Stdlib\Strings;
use Vanio\Stdlib\Uri;
use Vanio\UserBundle\Security\TargetPathResolver;

class UserExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
{
    /** @var UserManagerInterface */
    private $userManager;

    /** @var TargetPathResolver */
    private $targetPathResolver;

    /** @var RequestStack */
    private $requestStack;

    /** @var CsrfTokenManagerInterface|null */
    private $csrfTokenManager;

    /** @var mixed[] */
    private $config;

    /**
     * @param UserManagerInterface $userManager
     * @param TargetPathResolver $targetPathResolver
     * @param RequestStack $requestStack
     * @param CsrfTokenManagerInterface|null $tokenManager
     * @param mixed[] $config
     */
    public function __construct(
        UserManagerInterface $userManager,
        TargetPathResolver $targetPathResolver,
        RequestStack $requestStack,
        CsrfTokenManagerInterface $tokenManager = null,
        array $config
    ) {
        $this->userManager = $userManager;
        $this->targetPathResolver = $targetPathResolver;
        $this->requestStack = $requestStack;
        $this->csrfTokenManager = $tokenManager;
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
            new \Twig_SimpleFunction('target_path', [$this, 'targetPath']),
            new \Twig_SimpleFunction('is_trusted_api_url', [$this, 'isTrustedApiUrl']),
        ];
    }

    /**
     * @return mixed[]
     */
    public function getGlobals(): array
    {
        return ['vanio_user' => $this->config];
    }

    public function getName(): string
    {
        return 'vanio_user_extension';
    }

    public function getCsrfToken(string $tokenId): string
    {
        if (!$this->csrfTokenManager) {
            throw new \LogicException('CSRF token manager is not available. Is CSRF protection enabled?');
        }

        return $this->csrfTokenManager->getToken($tokenId);
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
    public function targetPath()
    {
        $request = $this->requestStack->getCurrentRequest();

        return $request ? $this->targetPathResolver->resolveTargetPath($request) : null;
    }

    public function isTrustedApiUrl(string $url): bool
    {
        $url = new Uri($url);

        foreach ($this->config['trusted_api_urls'] as $trustedApiUrl) {
            $trustedApiUrl = new Uri($trustedApiUrl);

            if (
                $trustedApiUrl->scheme() && $trustedApiUrl->scheme() !== $url->scheme()
                || $url->host() !== $trustedApiUrl->host()
            ) {
                continue;
            }

            $trustedApiUrlPath = rtrim($trustedApiUrl->path(), '/');
            $urlPath = rtrim($url->path(), '/');

            if ($urlPath === $trustedApiUrlPath || Strings::startsWith($urlPath, $trustedApiUrlPath . '/')) {
                return true;
            }
        }

        return false;
    }
}
