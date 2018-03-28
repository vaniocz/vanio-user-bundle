<?php
namespace Vanio\UserBundle\Controller;

use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Vanio\DiExtraBundle\Controller;
use Vanio\UserBundle\Form\ChangeEmailFormType;
use Vanio\UserBundle\Model\User;
use Vanio\WebBundle\Translation\FlashMessage;

class ChangeEmailController extends Controller
{
    public function confirmAction(Request $request, string $token)
    {
        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->get('security.token_storage');
        /** @var UserManagerInterface $userManager */
        $userManager = $this->get('fos_user.user_manager');

        /** @var User $user */
        $user = $userManager->findUserBy(['newEmailConfirmationToken' => $token]);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with "confirmation token" does not exist for value "%s"', $token));
        }

        $form = $this->createForm(ChangeEmailFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setEmail($user->getNewEmail());
            $user->removeNewEmailRequest();

            $userManager->updateUser($user);

            $flashMessage = new FlashMessage('change_email.flash.success', [], 'FOSUserBundle');
            $this->addFlash(FlashMessage::TYPE_SUCCESS, $flashMessage);

            $tokenStorage->setToken();

            return $this->redirectToRoute('fos_user_security_login');
        }

        return $this->render('@VanioUser/ChangeEmail/confirm.html.twig', [
            'token' => $token,
            'form' => $form->createView(),
        ]);
    }
}
