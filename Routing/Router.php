<?php
namespace Vanio\UserBundle\Routing;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Vanio\UserBundle\Security\TargetPathResolver;

class Router implements RouterInterface
{
    /** @var RouterInterface */
    private $router;

    /** @var TargetPathResolver|null */
    private $targetPathResolver;

    /** @var RequestStack */
    private $requestStack;

    public function __construct(RouterInterface $router, RequestStack $requestStack)
    {
        $this->router = $router;
        $this->requestStack = $requestStack;
    }

    public function setTargetPathResolver(TargetPathResolver $targetPathResolver)
    {
        $this->targetPathResolver = $targetPathResolver;
    }

    /**
     * @param string $pathinfo
     */
    public function match($pathinfo): array
    {
        return $this->router->match($pathinfo);
    }

    /**
     * @param string $name
     * @param array $parameters
     * @param int $referenceType
     * @return string
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH): string
    {
        if ($this->targetPathResolver && $name === 'fos_user_security_login') {
            if ($request = $this->requestStack->getCurrentRequest()) {
                if ($targetPath = $this->targetPathResolver->resolveTargetPath($request)) {
                    $parameters[$this->targetPathResolver->targetPathParameter()] = $targetPath;
                }
            }
        }

        return $this->router->generate($name, $parameters, $referenceType);
    }

    public function getRouteCollection(): RouteCollection
    {
        return $this->router->getRouteCollection();
    }

    public function setContext(RequestContext $context)
    {
        $this->router->setContext($context);
    }

    public function getContext(): RequestContext
    {
        return $this->router->getContext();
    }
}
