<?php
namespace Vanio\UserBundle\Controller;

use FOS\UserBundle\Controller\ResettingController as BaseResettingController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Vanio\WebBundle\Request\RefererHelperTrait;
use Vanio\WebBundle\Translation\FlashMessage;

class ResettingController extends BaseResettingController
{
    use RefererHelperTrait;

    /**
     * @param Request $request
     * @param string $token
     * @return Response
     */
    public function resetAction(Request $request, $token): Response
    {
        try {
            return parent::resetAction($request, $token);
        } catch (NotFoundHttpException $e) {
            $flashMessage = new FlashMessage('resetting.flash.confirmation_token_not_found', [], 'FOSUserBundle');
            $this->addFlash(FlashMessage::TYPE_DANGER, $flashMessage);

            return $this->redirectToReferer();
        }
    }
}
