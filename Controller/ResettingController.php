<?php
namespace Vanio\UserBundle\Controller;

use FOS\UserBundle\Controller\ResettingController as BaseResettingController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Vanio\WebBundle\Request\RefererHelperTrait;
use Vanio\WebBundle\Translation\FlashMessage;

class ResettingController extends BaseResettingController
{
    use RefererHelperTrait;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @param Request $request
     * @param string $token
     * @return Response
     */
    public function resetAction(Request $request, $token): Response
    {
        try {
            $response = parent::resetAction($request, $token);

            if (
                $response instanceof RedirectResponse
                && $response->getTargetUrl() === $this->generateUrl('fos_user_security_login')
            ) {
                return $this->createConfirmationTokenNotFoundResponse();
            }
        } catch (NotFoundHttpException $e) {
            return $this->createConfirmationTokenNotFoundResponse();
        }

        return $response;
    }

    private function createConfirmationTokenNotFoundResponse(): RedirectResponse
    {
        $flashMessage = new FlashMessage('resetting.flash.confirmation_token_not_found', [], 'FOSUserBundle');
        $this->addFlash(FlashMessage::TYPE_DANGER, $flashMessage);

        return $this->redirectToReferer();
    }
}
