<?php
namespace Vanio\UserBundle\Mailer;

use FOS\UserBundle\Mailer\TwigSwiftMailer as BaseTwigSwiftMailer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vanio\UserBundle\Model\User;

class TwigSwiftMailer extends BaseTwigSwiftMailer
{
    public function sendChangeEmailConfirmationMessage(User $user): void
    {
        $confirmationUrl = $this->router->generate(
            'vanio_user_change_email_confirm',
            ['token' => $user->getNewEmailConfirmationToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $data = [
            'user' => $user,
            'confirmationUrl' => $confirmationUrl,
        ];
        $this->sendMessage(
            $this->parameters['template']['change_email'],
            $data,
            $this->parameters['from_email']['change_email'],
            (string) $user->getNewEmail()
        );
    }
}
