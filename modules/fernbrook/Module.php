<?php

namespace modules\fernbrook;

use Craft;
use yii\base\Module as BaseModule;

/**
 * Fernbrook Roasters site module.
 *
 * Holds the console commands that build the content model and seed demo
 * content, so both are reproducible from an empty database instead of being
 * clicked together by hand.
 */
class Module extends BaseModule
{
    public function init(): void
    {
        Craft::setAlias('@modules/fernbrook', __DIR__);

        if (Craft::$app->request->getIsConsoleRequest()) {
            $this->controllerNamespace = 'modules\\fernbrook\\console\\controllers';
        } else {
            $this->controllerNamespace = 'modules\\fernbrook\\controllers';
        }

        parent::init();
    }
}
