<?php
namespace Vanio\UserBundle;

class VanioUserEvents
{
    /**
     * The REGISTRATION_CONFIRMATION_REQUEST event occurs just before user interactively requests confirmation email.
     *
     * @Event("FOS\UserBundle\Event\GetResponseNullableUserEvent")
     */
    public const REGISTRATION_CONFIRMATION_REQUEST = 'fos_user.registration.confirmation_request';

    /**
     * The REGISTRATION_CONFIRMATION_REQUESTED event occurs when user interactively requests confirmation email.
     * You can dispatch this event programatically in case you need to resend the email.
     *
     * @Event("FOS\UserBundle\Event\FormEvent")
     */
    public const REGISTRATION_CONFIRMATION_REQUESTED = 'fos_user.registration.confirmation_requested';

    /**
     * The RESETTING_RESET_FAILURE event occurs when the confirmation token is not found.
     *
     * @Event("FOS\UserBundle\Event\GetResponseNullableUserEvent")
     */
    public const RESETTING_RESET_FAILURE = 'fos_user.resetting.reset.failure';

    /**
     * The ACCOUNT_DISCONNECTED event occurs after user successfully disconnected social account.
     *
     * @Event("HWI\Bundle\OAuthBundle\Event\FilterUserResponseEvent")
     */
    public const ACCOUNT_DISCONNECTED = 'hwi_oauth.connect.account_disconnected';

    /**
     * The CHANGE_EMAIL_CONFIRMATION_SENT event occurs after confirmation email of email change has been sent.
     *
     * @Event("FOS\UserBundle\Event\GetResponseUserEvent")
     */
    public const CHANGE_EMAIL_CONFIRMATION_SENT = 'fos_user.change_email.confirmation_sent';

    /**
     * The CHANGE_EMAIL_INITIALIZE event occurs when the email change confirmation process is initialized.
     *
     * @Event("FOS\UserBundle\Event\GetResponseNullableUserEvent")
     */
    public const CHANGE_EMAIL_INITIALIZE = 'fos_user.change_email.initialize';

    /**
     * The CHANGE_EMAIL_FAILURE event occurs when the email is already used by another user inside the confirmation process.
     *
     * @Event("FOS\UserBundle\Event\FilterUserResponseEvent")
     */
    public const CHANGE_EMAIL_FAILURE = 'fos_user.change_email.failure';

    /**
     * The CHANGE_EMAIL_SUCCESS event occurs when the email change confirmation form is submitted successfully.
     *
     * @Event("FOS\UserBundle\Event\FormEvent")
     */
    public const CHANGE_EMAIL_SUCCESS = 'fos_user.change_email.success';

    /**
     * The CHANGE_EMAIL_COMPLETED event occurs after saving the user in the email change confirmation process.
     *
     * @Event("FOS\UserBundle\Event\FilterUserResponseEvent")
     */
    public const CHANGE_EMAIL_COMPLETED = 'fos_user.change_email.completed';

    /**
     * The UNREGISTRATION_COMPLETED event occurs after deleting the user.
     *
     * @Event("FOS\UserBundle\Event\FilterUserResponseEvent")
     */
    public const UNREGISTRATION_COMPLETED = 'fos_user.registration.unregistration_completed';
}
