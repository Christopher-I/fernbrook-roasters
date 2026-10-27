<?php
/**
 * Redirects held in code.
 *
 * These are the permanent ones that belong with the deploy: URLs from the
 * site this one replaced, which should keep working forever and should be
 * reviewable in a pull request rather than edited on a live server.
 *
 * Redirects that the agency needs to add themselves, and the 404 log that
 * shows which are needed, live in the Redirects plugin in the control panel.
 *
 * Evaluated only after Craft would otherwise have thrown a 404.
 *
 * @link https://craftcms.com/docs/5.x/system/routing.html#redirection
 */

return [
    // The old brochure site kept everything under /shop/.
    [
        'from' => 'shop/wholesale.html',
        'to' => 'services/wholesale-coffee',
        'statusCode' => 301,
    ],
    // Printed on the 2023 bags and still scanned from time to time.
    [
        'from' => 'our-cafes',
        'to' => 'visit',
        'statusCode' => 301,
    ],
];
