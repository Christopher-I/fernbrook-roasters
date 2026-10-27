<?php
/**
 * Contact form settings.
 *
 * Driven by .env so the destination address differs between environments.
 * Spam protection is a honeypot field, handled in the Fernbrook module.
 */

use craft\helpers\App;

return [
    'toEmail' => App::env('CONTACT_FORM_TO_EMAIL') ?: App::env('MAIL_FROM_EMAIL'),
    'prependSender' => '',
    'prependSubject' => '[Website]',
    'successFlashMessage' => 'Thanks, that has reached us.',
    'allowAttachments' => false,
];
