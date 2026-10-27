<?php

namespace modules\fernbrook;

use Craft;
use craft\contactform\events\SendEvent;
use craft\contactform\Mailer;
use yii\base\Event;
use yii\base\Module as BaseModule;

/**
 * Fernbrook Roasters site module.
 *
 * Holds the console commands that build the content model and seed demo
 * content, plus the contact form's spam check.
 */
class Module extends BaseModule
{
    /**
     * @var string The name of the honeypot field on the contact form. Bots
     * fill every input they find; people never see this one.
     */
    public const HONEYPOT_FIELD = 'fbr_website';

    public function init(): void
    {
        Craft::setAlias('@modules/fernbrook', __DIR__);

        if (Craft::$app->request->getIsConsoleRequest()) {
            $this->controllerNamespace = 'modules\\fernbrook\\console\\controllers';
        } else {
            $this->controllerNamespace = 'modules\\fernbrook\\controllers';
        }

        parent::init();

        $this->registerSpamCheck();
    }

    /**
     * Marks a submission as spam when the honeypot field has been filled in.
     *
     * The plugin then drops the message and still reports success, so a bot
     * gets no signal that it was caught.
     */
    private function registerSpamCheck(): void
    {
        if (!Craft::$app->getPlugins()->isPluginEnabled('contact-form')) {
            return;
        }

        Event::on(Mailer::class, Mailer::EVENT_BEFORE_SEND, function(SendEvent $event) {
            $trap = Craft::$app->getRequest()->getBodyParam(self::HONEYPOT_FIELD);

            if ($trap !== null && $trap !== '') {
                Craft::info('Contact form submission rejected by the honeypot.', __METHOD__);
                $event->isSpam = true;
            }
        });
    }
}
