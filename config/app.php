<?php
/**
 * Yii application config.
 *
 * @link https://craftcms.com/docs/5.x/reference/config/app.html
 */

use craft\helpers\App;

return [
    'id' => App::env('CRAFT_APP_ID') ?: 'CraftCMS',
    'modules' => [
        'fernbrook' => \modules\fernbrook\Module::class,
    ],
    'bootstrap' => ['fernbrook'],
];
