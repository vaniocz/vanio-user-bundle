<?php
namespace Vanio\UserBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Vanio\Stdlib\Strings;

/**
 * Resolves target path for redirecting after successful login.
 */
class TargetPathResolver
{
    /** @var HttpUtils */
    private $httpUtils;

    /** @var RouterInterface */
    private $router;

    /** @var mixed[] */
    private $options = [
        'target_path_parameter' => '_target_path',
        'default_target_path' => '/',
        'ignored_routes' => [],
        'ignored_route_prefixes' => [],
    ];

    /**
     * @param RouterInterface $urlGenerator
     * @param HttpUtils $httpUtils
     * @param mixed[] $options
     */
    public function __construct(RouterInterface $urlGenerator, HttpUtils $httpUtils, array $options = [])
    {
        $this->router = $urlGenerator;
        $this->httpUtils = $httpUtils;
        $this->options = $options + $this->options;
        $this->options['ignored_route_prefixes'] = array_merge($this->options['ignored_route_prefixes'], [
            'fos_user_security_',
            'fos_user_registration_',
            'fos_user_resetting_',
            'hwi_oauth_',
        ]);
    }

    public function resolveTargetPath(Request $request): ?string
    {
        $route = $request->attributes->get('_route');

        if ($route === 'fos_user_security_login') {
            return $this->resolveTargetPathFromParameterValue($request);
        } elseif (
            $route === null
            || !$request->isMethod('GET')
            || in_array($route, $this->options['ignored_routes'])
            || Strings::startsWith($route, $this->options['ignored_route_prefixes'])
        ) {
            return null;
        }

        $queryString = $request->getQueryString();
        $targetPath = rawurldecode($request->getPathInfo() . ($queryString === null ? '' : '?' . $queryString));
        $absoluteBaseUrl = $request->getSchemeAndHttpHost() . $request->getBaseUrl();
        $defaultTargetPath = $this->httpUtils->generateUri($request, $this->options['default_target_path']);
        $defaultTargetPath = str_replace($absoluteBaseUrl, '', $defaultTargetPath);

        return $targetPath === $defaultTargetPath ? null : $targetPath;
    }

    public function resolveTargetPathFromParameterValue(Request $request): ?string
    {
        $absoluteBaseUrl = $request->getSchemeAndHttpHost() . $request->getBaseUrl();
        $targetPath = $request->get($this->targetPathParameter());

        if (!is_string($targetPath)) {
            return null;
        } elseif (Strings::startsWith($targetPath, $absoluteBaseUrl)) {
            $targetPath = substr($targetPath, strlen($absoluteBaseUrl));
        }

        $method = $this->router->getContext()->getMethod();

        try {
            if (Strings::startsWith($targetPath, '/')) {
                $path = parse_url($targetPath, PHP_URL_PATH);

                if ($path !== false) {
                    $this->router->getContext()->setMethod('GET');
                    $this->router->match($path);
                }
            } else {
                $this->router->generate($targetPath);
            }
        } catch (ExceptionInterface $e) {
            $targetPath = null;
        }

        $this->router->getContext()->setMethod($method);

        return $targetPath;
    }

    public function targetPathParameter(): string
    {
        return $this->options['target_path_parameter'];
    }

    public function defaultTargetPath(): string
    {
        return $this->options['default_target_path'];
    }
}
