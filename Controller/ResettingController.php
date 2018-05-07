<?php
namespace Vanio\UserBundle\Controller;

use FOS\UserBundle\Controller\ResettingController as BaseResettingController;
use FOS\UserBundle\Event\GetResponseNullableUserEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Vanio\UserBundle\VanioUserEvents;
use Vanio\WebBundle\Request\RefererHelperTrait;

class ResettingController extends BaseResettingController
{
    use ResponseFormatTrait;
    use RefererHelperTrait;

    public function sendEmailAction(Request $request): Response
    {
        if (!$request->request->has('username')) {
            $request->request->set('username', $request->request->get('email'));
        }

        return parent::sendEmailAction($request);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @param Request $request
     * @param string $token
     * @return Response
     */
    public function resetAction(Request $request, $token): Response
    {
        $response = parent::resetAction($request, $token);

        if ($response->isRedirect($this->generateUrl('fos_user_security_login'))) {
            $event = new GetResponseNullableUserEvent(null, $request);
            $this->eventDispatcher()->dispatch(VanioUserEvents::RESETTING_RESET_FAILURE, $event);

            return $event->getResponse() ?: $this->redirectToReferer();
        }

        return $response;
    }

    private function eventDispatcher(): EventDispatcherInterface
    {
        return $this->get('event_dispatcher');
    }
}
