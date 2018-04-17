<?php
namespace Vanio\UserBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\HttpUtils;

/**
 * Uses target path resolver when determining target path, ignores session.
 */
class AuthenticationSuccessHandler extends DefaultAuthenticationSuccessHandler
{
    /** @var TargetPathResolver */
    private $targetPathResolver;

    /**
     * @param HttpUtils $httpUtils
     * @param mixed[] $options
     * @param TargetPathResolver $targetPathResolver
     */
    public function __construct(HttpUtils $httpUtils, array $options, TargetPathResolver $targetPathResolver)
    {
        parent::__construct($httpUtils, $options);
        $this->targetPathResolver = $targetPathResolver;
    }

    protected function determineTargetUrl(Request $request): string
    {
        if ($this->options['always_use_default_target_path']) {
            return $this->options['default_target_path'];
        } elseif ($targetUrl = $this->targetPathResolver->resolveTargetPathFromParameterValue($request)) {
            return $targetUrl;
        }

        return $this->targetPathResolver->defaultTargetPath();
    }
}
