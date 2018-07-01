<?php
namespace Vanio\UserBundle\Mailer;

use FOS\UserBundle\Mailer\MailerInterface;
use Vanio\UserBundle\Model\User;

interface Mailer extends MailerInterface
{
    function sendChangeEmailConfirmationMessage(User $user): void;
}
