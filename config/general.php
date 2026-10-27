<?php
/**
 * General configuration.
 *
 * One file, environment differences applied at the bottom. Everything that
 * varies per environment comes from .env rather than being hard-coded here,
 * so the same config ships to every environment.
 *
 * @see \craft\config\GeneralConfig
 * @link https://craftcms.com/docs/5.x/reference/config/general.html
 */

use craft\config\GeneralConfig;
use craft\helpers\App;

$isDev = App::env('CRAFT_ENVIRONMENT') === 'dev';

$config = GeneralConfig::create()
    ->defaultWeekStartDay(1)
    ->omitScriptNameInUrls()
    ->preloadSingles()
    ->preventUserEnumeration()
    ->loginPath(false)
    ->enableTwigSandbox()
    ->errorTemplatePrefix('_errors/')
    // Write transforms during the request rather than serving a redirect
    // through actions/assets/generate-transform, so the HTML carries real
    // image URLs and the browser does not pay a round trip per image.
    ->generateTransformsBeforePageLoad()
    ->defaultSearchTermOptions([
        'subLeft' => true,
        'subRight' => true,
    ])
    // Keep the control panel on a predictable path and out of the sitemap.
    ->cpTrigger('admin')
    ->aliases([
        '@webroot' => dirname(__DIR__) . '/web',
        '@web' => App::env('PRIMARY_SITE_URL'),
        // Cache buster for the stylesheet. There is no build step, so the
        // file's own modification time is the version: edit the CSS and the
        // URL changes, leave it alone and browsers keep the cached copy.
        '@assetVersion' => (string)(@filemtime(dirname(__DIR__) . '/web/css/site.css') ?: 0),
    ]);

if ($isDev) {
    // Local: no template caching, so a Twig edit shows up on refresh, and
    // generated URLs are allowed to change with the DDEV hostname.
    $config
        ->enableTemplateCaching(false)
        ->allowAdminChanges(true)
        ->disallowRobots(true)
        ->runQueueAutomatically(true);
} else {
    // Production: templates compiled once, schema changes arrive through
    // project config and `php craft up`, never through the control panel.
    $config
        ->enableTemplateCaching(true)
        ->allowAdminChanges(false)
        ->disallowRobots(false)
        // The queue runs as its own supervised process in the container, so
        // it must not also be kicked off by web requests.
        ->runQueueAutomatically(false)
        ->cacheDuration('P1W');
}

return $config;
