<?php
namespace Vanio\UserBundle\Mailer;

use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vanio\EasyMailer\EasyMailer as VanioEasyMailer;
use Vanio\UserBundle\Model\User;

class EasyMailer implements Mailer
{
    /** @var VanioEasyMailer */
    private $mailer;

    /** @var UrlGeneratorInterface */
    private $router;

    /** @var mixed[] */
    private $parameters;

    /**
     * @param VanioEasyMailer $mailer
     * @param UrlGeneratorInterface $router
     * @param mixed[] $parameters
     */
    public function __construct(VanioEasyMailer $mailer, UrlGeneratorInterface $router, array $parameters)
    {
        $this->mailer = $mailer;
        $this->router = $router;
        $this->parameters = $parameters;
    }

    public function sendConfirmationEmailMessage(UserInterface $user): void
    {
        $confirmationUrl = $this->router->generate(
            'fos_user_registration_confirm',
            ['token' => $user->getConfirmationToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $context = [
            'from' => $this->parameters['from_email']['confirmation'],
            'user' => $user,
            'confirmationUrl' => $confirmationUrl,
        ];
        $this->mailer->send($this->parameters['template']['confirmation'], $context, [$user->getEmail()]);
    }

    public function sendResettingEmailMessage(UserInterface $user): void
    {
        $confirmationUrl = $this->router->generate(
            'fos_user_resetting_reset',
            ['token' => $user->getConfirmationToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $context = [
            'from' => $this->parameters['from_email']['resetting'],
            'user' => $user,
            'confirmationUrl' => $confirmationUrl,
        ];
        $this->mailer->send($this->parameters['template']['resetting'], $context, [$user->getEmail()]);
    }

    public function sendChangeEmailConfirmationMessage(User $user): void
    {
        $confirmationUrl = $this->router->generate(
            'vanio_user_change_email_confirm',
            ['token' => $user->getNewEmailConfirmationToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $context = [
            'from' => $this->parameters['from_email']['change_email'],
            'user' => $user,
            'confirmationUrl' => $confirmationUrl,
        ];
        $this->mailer->send($this->parameters['template']['change_email'], $context, [$user->getNewEmail()]);
    }
}
