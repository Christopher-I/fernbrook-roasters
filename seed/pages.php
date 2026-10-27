<?php
/**
 * Demo Pages for Fernbrook Roasters.
 *
 * Each page is built from the same Matrix page builder an editor would use in
 * the control panel, so the seeded content exercises every block type.
 */

return [
    [
        'title' => 'Fernbrook Roasters',
        'slug' => 'home',
        'summary' => 'A small coffee roastery and two shops in Ashworth. Roasted to order, delivered the same week.',
        'hero' => 'home-hero.jpg',
        'hideFromNav' => true,
        'seoTitle' => 'Fernbrook Roasters, Ashworth',
        'seoDescription' => 'An independent coffee roaster in Ashworth with shops at Arch 14 and Millgate. Wholesale, training, servicing and subscriptions.',
        'blocks' => [
            ['type' => 'blockRichText', 'fields' => ['bodyText' => <<<HTML
<h2>Roasted under the viaduct, twice a week</h2>
<p>Fernbrook is a two-shop roastery in Ashworth. We buy green coffee a few sacks at a time, roast it on Tuesdays and Wednesdays, and sell it before it has time to get old. There is a bar at the roastery and a smaller shop on the high street in Netherfield.</p>
HTML]],
            ['type' => 'blockTwoColumn', 'fields' => [
                'columnLeft' => '<h3>For people who drink it</h3><p>Two shops, a retail shelf that changes with the harvest, and a subscription you can pause whenever you want. Collection is cheaper than postage and the coffee is a day fresher.</p>',
                'columnRight' => '<h3>For people who sell it</h3><p>Wholesale with no contract and a five kilo minimum, training in the roastery on Mondays, and servicing for the machine when it stops behaving.</p>',
            ]],
            ['type' => 'blockImage', 'fields' => [
                'blockImage' => 'block-roastery.jpg',
                'imageCaption' => 'The drum at Arch 14, mid-batch.',
                'imageWidth' => 'full',
            ]],
            ['type' => 'blockPullQuote', 'fields' => [
                'quoteText' => 'We would rather you paused the subscription for a month than ended up with four unopened bags in a cupboard.',
                'quoteAttribution' => 'Nadia Okoro, Head Roaster',
            ]],
            ['type' => 'blockCta', 'fields' => [
                'ctaHeading' => 'Come and taste something',
                'ctaText' => 'We run a free open cupping at Arch 14 on the last Saturday of the month. No experience needed, and it takes an hour.',
                'showButton' => true,
                'buttonLabel' => 'Find us',
                'buttonLink' => ['entry' => 'visit/arch-14'],
            ]],
        ],
    ],
    [
        'title' => 'About',
        'slug' => 'about',
        'summary' => 'Who we are, what we buy, and how the roastery actually runs.',
        'hero' => 'about-hero.jpg',
        'seoDescription' => 'Fernbrook Roasters has been roasting in Ashworth since 2017. How we buy green coffee and how the roastery runs.',
        'blocks' => [
            ['type' => 'blockRichText', 'fields' => ['bodyText' => <<<HTML
<h2>Since 2017</h2>
<p>Fernbrook started with a 5kg roaster in the back of a railway arch and an order book of four cafés. The machine is bigger now and there are two shops, but the shape of the business has not really changed: buy a small amount of good green coffee, roast it carefully, sell it quickly.</p>
<p>There are seven of us. Everybody works a bar shift, including whoever is on the roaster that week, because it is the fastest way to find out whether what we made is any good.</p>
HTML]],
            ['type' => 'blockTwoColumn', 'fields' => [
                'columnLeft' => '<h3>How we buy</h3><p>Mostly through two importers we have worked with for years, plus one direct relationship in Colombia. We pay above the going rate for the lots we like and we publish what we paid when the producer is happy for us to.</p>',
                'columnRight' => '<h3>How we roast</h3><p>Two roast days a week, profiles written down and cupped against every batch. Development time is the number we care about most. Anything that drifts gets pulled before it leaves the building.</p>',
            ]],
            ['type' => 'blockImage', 'fields' => [
                'blockImage' => 'block-beans.jpg',
                'imageCaption' => 'Sorting out the defects before a roast.',
                'imageWidth' => 'inset',
            ]],
            ['type' => 'blockPullQuote', 'fields' => [
                'quoteText' => 'Everybody works a bar shift, including whoever is on the roaster that week.',
                'quoteAttribution' => '',
            ]],
            ['type' => 'blockRichText', 'fields' => ['bodyText' => <<<HTML
<h2>The shops</h2>
<p>Arch 14 is the roastery and the busier of the two. Millgate is smaller, quieter, and the better one to sit and work in. Both serve the same coffee and both will grind retail bags for whatever you brew on.</p>
HTML]],
            ['type' => 'blockCta', 'fields' => [
                'ctaHeading' => 'Work with us',
                'ctaText' => 'We are usually looking for one or two people. Bar experience helps, enthusiasm helps more.',
                'showButton' => true,
                'buttonLabel' => 'Get in touch',
                'buttonLink' => ['entry' => 'contact'],
            ]],
        ],
    ],
    [
        'title' => 'Our coffee',
        'slug' => 'our-coffee',
        'summary' => 'What is on the shelf at the moment and how to choose between it.',
        'hero' => 'service-subscriptions.jpg',
        'seoDescription' => 'The coffee Fernbrook Roasters has on the shelf, how it is roasted, and how to pick between espresso and filter.',
        'blocks' => [
            ['type' => 'blockRichText', 'fields' => ['bodyText' => <<<HTML
<h2>Three things, most of the time</h2>
<p>A house espresso that stays broadly the same through the year, a rotating single origin for filter, and a decaf we are not embarrassed by. When something unusual turns up we put it on as a fourth and it tends not to last long.</p>
<h3>House espresso</h3>
<p>A blend, adjusted through the year so the cup lands in the same place as the components change. Chocolate, brown sugar, enough acidity to cut through milk. This is what both bars pull by default.</p>
<h3>Single origin filter</h3>
<p>Whatever is best and in season. At the moment that is a washed Ethiopian from the Yirgacheffe area, which is doing the floral and citrus thing that makes people who say they do not like black coffee reconsider.</p>
HTML]],
            ['type' => 'blockImage', 'fields' => [
                'blockImage' => 'block-counter.jpg',
                'imageCaption' => 'The retail shelf at Millgate.',
                'imageWidth' => 'wide',
            ]],
            ['type' => 'blockCta', 'fields' => [
                'ctaHeading' => 'Not sure which to pick?',
                'ctaText' => 'Tell us how you brew and we will send something that suits it. That is the whole point of the subscription.',
                'showButton' => true,
                'buttonLabel' => 'See subscriptions',
                'buttonLink' => ['entry' => 'services/coffee-subscriptions'],
            ]],
        ],
    ],
    [
        'title' => 'Contact',
        'slug' => 'contact',
        'summary' => 'Wholesale enquiries, training bookings, or anything else.',
        'hero' => 'contact-hero.jpg',
        'seoDescription' => 'Get in touch with Fernbrook Roasters about wholesale coffee, barista training, equipment servicing or subscriptions.',
        'blocks' => [
            ['type' => 'blockRichText', 'fields' => ['bodyText' => <<<HTML
<h2>Talk to us</h2>
<p>The quickest answers come by phone during shop hours. For anything involving numbers, the form below is better, because it gets to whoever is dealing with that rather than whoever picked up.</p>
HTML]],
            ['type' => 'blockForm', 'fields' => [
                'formHeading' => 'Send us a message',
                'formIntro' => 'We answer most enquiries the same working day. Wholesale enquiries usually get a reply with prices attached.',
            ]],
        ],
        'children' => [
            [
                'title' => 'Thank you',
                'slug' => 'thank-you',
                'summary' => 'Your message has been sent.',
                'hideFromNav' => true,
                'blocks' => [
                    ['type' => 'blockRichText', 'fields' => ['bodyText' => <<<HTML
<h2>Thanks, that has reached us</h2>
<p>We will come back to you by email, usually the same working day. If it is urgent, the shop phone is the faster route.</p>
HTML]],
                    ['type' => 'blockCta', 'fields' => [
                        'ctaHeading' => 'While you wait',
                        'ctaText' => 'The journal is where we write down the things we end up explaining over the counter anyway.',
                        'showButton' => true,
                        'buttonLabel' => 'Read the journal',
                        'buttonLink' => ['url' => '/journal'],
                    ]],
                ],
            ],
        ],
    ],
    [
        'title' => 'Privacy',
        'slug' => 'privacy',
        'summary' => 'What we do with the information you give us.',
        'hideFromNav' => true,
        'blocks' => [
            ['type' => 'blockRichText', 'fields' => ['bodyText' => <<<HTML
<h2>What we collect</h2>
<p>This is a demonstration site for a business that does not exist, so nothing here is collected or stored beyond what is needed to show the contact form working. On a real site this page would set out what is collected, why, how long it is kept and who it is shared with.</p>
<h2>The contact form</h2>
<p>Messages sent through the form are delivered by email to the site owner. They are not added to a mailing list.</p>
<h2>Cookies</h2>
<p>The site sets one session cookie, which Craft uses to protect the contact form against cross-site request forgery. There is no analytics or advertising tracking.</p>
HTML]],
        ],
    ],
];
