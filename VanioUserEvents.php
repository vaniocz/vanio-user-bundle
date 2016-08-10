<?php
namespace Vanio\UserBundle;

class VanioUserEvents
{
    /**
     * The REGISTRATION_CONFIRMATION_REQUESTED event occurs when user interactively requests confirmation email.
     * You can dispatch this event programatically in case you need to resend the email.
     *
     * @Event("FOS\UserBundle\Event\FormEvent")
     */
    const REGISTRATION_CONFIRMATION_REQUESTED = 'fos_user.registration.confirmation_requested';
}
