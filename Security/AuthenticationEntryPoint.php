<?php
namespace Vanio\UserBundle\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Vanio\Stdlib\Uri;
use Vanio\WebBundle\Translation\FlashMessage;

/**
 * Notifies about missing authentication using a flash message and/or passes target path when redirecting to an entry
 * point.
 */
class AuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    /** @var AuthenticationEntryPointInterface */
    private $authenticationEntryPoint;

    /** @var TargetPathResolver */
    private $targetPathResolver;

    /** @var mixed[] */
    private $options;

    /**
     * @param AuthenticationEntryPointInterface $authenticationEntryPoint
     * @param TargetPathResolver $targetPathResolver
     * @param mixed[] $options
     */
    public function __construct(
        AuthenticationEntryPointInterface $authenticationEntryPoint,
        TargetPathResolver $targetPathResolver,
        array $options
    ) {
        $this->authenticationEntryPoint = $authenticationEntryPoint;
        $this->targetPathResolver = $targetPathResolver;
        $this->options = $options + [
            'pass_target_path' => ['enabled' => false],
            'use_flash_notifications' => true,
        ];
    }

    public function start(Request $request, AuthenticationException $authenticationException = null): Response
    {
        $response = $this->authenticationEntryPoint->start($request, $authenticationException);

        if (($this->options['pass_target_path']['enabled'] ?? false) && $response instanceof RedirectResponse) {
            $this->passTargetPath($request, $response);
        }

        if ($this->options['use_flash_notifications']) {
            $this->notify($request);
        }

        return $response;
    }

    private function passTargetPath(Request $request, RedirectResponse $response)
    {
        if ($targetPath = $this->targetPathResolver->resolveTargetPath($request)) {
            $targetUri = (new Uri($response->getTargetUrl()))->withAppendedQuery([
                $this->targetPathResolver->targetPathParameter() => $targetPath,
            ]);
            $response->setTargetUrl($targetUri->absoluteUri());
        }
    }

    private function notify(Request $request)
    {
        $session = $request->getSession();

        if ($session instanceof Session) {
            $flashMessage = new FlashMessage('security.flash.login_needed', [], 'FOSUserBundle');
            $session->getFlashBag()->add(FlashMessage::TYPE_WARNING, $flashMessage);
        }
    }
}
