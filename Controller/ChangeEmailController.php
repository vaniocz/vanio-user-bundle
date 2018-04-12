<?php
namespace Vanio\UserBundle\Controller;

use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\HttpUtils;
use Vanio\UserBundle\Form\ChangeEmailFormType;
use Vanio\UserBundle\Model\User;
use Vanio\WebBundle\Translation\FlashMessage;

class ChangeEmailController extends Controller
{
    public function confirmAction(Request $request, string $token): Response
    {
        /** @var User $user */
        $user = $this->userManager()->findUserBy(['newEmailConfirmationToken' => $token]);

        if (!is_a($user, User::class)) {
            $this->addFlashMessage(FlashMessage::TYPE_DANGER, 'change_email.flash.confirmation_token_not_found');

            return $this->createRedirectResponse($request);
        }

        $form = $this->createForm(ChangeEmailFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setEmail($user->getNewEmail());
            $user->removeNewEmailRequest();
            $this->userManager()->updateUser($user);

            $this->addFlashMessage(FlashMessage::TYPE_SUCCESS, 'change_email.flash.success');

            return $this->createRedirectResponse($request);
        }

        return $this->render('@VanioUser/ChangeEmail/confirm.html.twig', [
            'token' => $token,
            'form' => $form->createView(),
        ]);
    }

    private function createRedirectResponse(Request $request): RedirectResponse
    {
        return $this->httpUtils()->createRedirectResponse(
            $request,
            $this->getParameter('vanio_user.change_email.target_path')
        );
    }

    /**
     * @param string $type
     * @param string $message
     * @param mixed[] $parameters
     */
    private function addFlashMessage(string $type, string $message, array $parameters = [])
    {
        $this->addFlash($type, new FlashMessage($message, $parameters, 'FOSUserBundle'));
    }

    private function userManager(): UserManagerInterface
    {
        return $this->get('fos_user.user_manager');
    }

    private function httpUtils(): HttpUtils
    {
        return $this->get('security.http_utils');
    }
}
