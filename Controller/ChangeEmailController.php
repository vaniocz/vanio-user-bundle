<?php
namespace Vanio\UserBundle\Controller;

use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Vanio\DiExtraBundle\Controller;
use Vanio\UserBundle\Form\ChangeEmailFormType;
use Vanio\UserBundle\Model\User;
use Vanio\WebBundle\Request\RefererHelperTrait;
use Vanio\WebBundle\Translation\FlashMessage;

class ChangeEmailController extends Controller
{
    use RefererHelperTrait;

    public function confirmAction(Request $request, string $token)
    {
        $user = $this->userManager()->findUserBy(['newEmailConfirmationToken' => $token]);

        if (!is_a($user, User::class, true)) {
            $this->addFlashMessage(FlashMessage::TYPE_DANGER, 'change_email.flash.confirmation_token_not_found');

            return $this->redirectToReferer();
        }

        $form = $this->createForm(ChangeEmailFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setEmail($user->getNewEmail());
            $user->removeNewEmailRequest();
            $this->userManager()->updateUser($user);

            $this->addFlashMessage(FlashMessage::TYPE_SUCCESS, 'change_email.flash.success');
            $this->tokenStorage()->setToken(null);

            return $this->redirectToRoute('fos_user_security_login');
        }

        return $this->render('@VanioUser/ChangeEmail/confirm.html.twig', [
            'token' => $token,
            'form' => $form->createView(),
        ]);
    }

    private function addFlashMessage(string $type, string $message, array $parameters = [])
    {
        $this->addFlash($type, new FlashMessage($message, $parameters, 'FOSUserBundle'));
    }

    private function tokenStorage(): TokenStorageInterface
    {
        return $this->get('security.token_storage');
    }

    private function userManager(): UserManagerInterface
    {
        return $this->get('fos_user.user_manager');
    }
}
