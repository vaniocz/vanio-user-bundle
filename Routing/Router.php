<?php
namespace Vanio\UserBundle\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Vanio\Stdlib\Strings;
use Vanio\UserBundle\Security\TargetPathResolver;

class Router implements RouterInterface, RequestMatcherInterface
{
    /** @var RouterInterface */
    private $router;

    /** @var TargetPathResolver|null */
    private $targetPathResolver;

    /** @var bool */
    private $passTargetPath = false;

    /** @var RequestStack */
    private $requestStack;

    public function __construct(RouterInterface $router, RequestStack $requestStack)
    {
        $this->router = $router;
        $this->requestStack = $requestStack;
    }

    public function setTargetPathResolver(TargetPathResolver $targetPathResolver): void
    {
        $this->targetPathResolver = $targetPathResolver;
    }

    public function setPassTargetPath(bool $passTargetPath): void
    {
        $this->passTargetPath = $passTargetPath;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @param string $pathinfo
     * @return string[]
     */
    public function match($pathinfo): array
    {
        return $this->router->match($pathinfo);
    }

    /**
     * @return mixed[]
     */
    public function matchRequest(Request $request): array
    {
        return $this->router instanceof RequestMatcherInterface
            ? $this->router->matchRequest($request)
            : $this->match($request->getPathInfo());
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @param string $name
     * @param string[] $parameters
     * @param int $referenceType
     * @return string
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH): string
    {
        if ($request = $this->requestStack->getCurrentRequest()) {
            if ($this->targetPathResolver && $this->passTargetPath && $name === 'fos_user_security_login') {
                if ($targetPath = $this->targetPathResolver->resolveTargetPath($request)) {
                    $parameters[$this->targetPathResolver->targetPathParameter()] = $targetPath;
                }
            }

            $namePrefix = $request->attributes->get('_user_route_name_prefix');

            if ($namePrefix && Strings::startsWith($name, ['fos_user_', 'vanio_user_'])) {
                try {
                    return $this->router->generate($namePrefix . $name, $parameters, $referenceType);
                } catch (ExceptionInterface $e) {}
            }
        }

        return $this->router->generate($name, $parameters, $referenceType);
    }

    public function getRouteCollection(): RouteCollection
    {
        return $this->router->getRouteCollection();
    }

    public function setContext(RequestContext $context): void
    {
        $this->router->setContext($context);
    }

    public function getContext(): RequestContext
    {
        return $this->router->getContext();
    }
}
