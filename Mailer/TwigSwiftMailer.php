<?php
namespace Vanio\UserBundle\Mailer;

use FOS\UserBundle\Mailer\TwigSwiftMailer as BaseTwigSwiftMailer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vanio\UserBundle\Model\User;

class TwigSwiftMailer extends BaseTwigSwiftMailer
{
    public function sendNewEmailConfirmationMessage(User $user)
    {
        $template = $this->parameters['template']['new_email'];
        $url = $this->router->generate(
            'vanio_user_new_email_confirm',
            ['token' => $user->getNewEmailConfirmationToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $context = [
            'user' => $user,
            'confirmationUrl' => $url,
        ];

        $this->sendMessage($template, $context, $this->parameters['from_email']['new_email'], (string) $user->getNewEmail());
    }
}
