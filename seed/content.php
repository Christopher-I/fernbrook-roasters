<?php
/**
 * Demo content for Fernbrook Roasters.
 *
 * Fernbrook Roasters is invented. The addresses, phone number and people are
 * invented with it. The telephone number uses Ofcom's 01632 960xxx range,
 * which is reserved for fiction and never connects to anyone.
 */

return [
    'author' => [
        'fullName' => 'Nadia Okoro',
        'role' => 'Head Roaster',
        'bio' => "Nadia has run Fernbrook's roastery since the first batch came off the drum in 2017. She buys most of the green coffee herself, writes the roast profiles, and spends Thursday mornings on the bar at Arch 14 so she can hear what people actually think.",
        'photo' => 'author-portrait.jpg',
    ],

    'globals' => [
        'contactPhone' => '01632 960114',
        'contactEmail' => 'hello@fernbrookroasters.co.uk',
        'postalAddress' => "Fernbrook Roasters\nUnit 14, Fernbrook Arches\nAshworth AS1 4QD",
        'socialLinks' => [
            ['network' => 'Instagram', 'url' => 'https://www.instagram.com/'],
            ['network' => 'Bluesky', 'url' => 'https://bsky.app/'],
        ],
        'announcementEnabled' => true,
        'announcementText' => 'Arch 14 is open late on Thursdays through the winter, until 8pm.',
        'announcementLink' => ['entry' => 'visit/arch-14'],
    ],

    'categories' => [
        ['title' => 'Roasting', 'slug' => 'roasting', 'summary' => 'What happens between green coffee and the bag.'],
        ['title' => 'Brewing', 'slug' => 'brewing', 'summary' => 'Getting a better cup out of the coffee you already own.'],
        ['title' => 'Origins', 'slug' => 'origins', 'summary' => 'The farms and co-operatives we buy from, and why.'],
        ['title' => 'The shops', 'slug' => 'the-shops', 'summary' => 'News from Arch 14 and Millgate.'],
    ],

    'locations' => [
        [
            'title' => 'Arch 14',
            'slug' => 'arch-14',
            'summary' => 'The roastery and our first shop, under the viaduct at the east end of Ashworth.',
            'image' => 'location-arch-14.jpg',
            'body' => <<<HTML
<p>Everything Fernbrook sells is roasted here, in the back half of a railway arch that used to be a tyre fitter's. The bar at the front seats eighteen, the roaster runs most of Tuesday and Wednesday, and if you turn up on a roasting day you will smell it from the car park.</p>
<p>There is step-free access from Fernbrook Lane. The nearest parking is the pay-and-display on Calder Street, two minutes away.</p>
HTML,
            'addressLine1' => 'Unit 14, Fernbrook Arches',
            'addressLine2' => 'Fernbrook Lane',
            'addressCity' => 'Ashworth',
            'addressPostcode' => 'AS1 4QD',
            'mapUrl' => 'https://www.openstreetmap.org/search?query=Ashworth',
            'openingHours' => [
                ['day' => 'Monday', 'opens' => '7:30', 'closes' => '16:00', 'closed' => false],
                ['day' => 'Tuesday', 'opens' => '7:30', 'closes' => '16:00', 'closed' => false],
                ['day' => 'Wednesday', 'opens' => '7:30', 'closes' => '16:00', 'closed' => false],
                ['day' => 'Thursday', 'opens' => '7:30', 'closes' => '20:00', 'closed' => false],
                ['day' => 'Friday', 'opens' => '7:30', 'closes' => '16:00', 'closed' => false],
                ['day' => 'Saturday', 'opens' => '8:30', 'closes' => '17:00', 'closed' => false],
                ['day' => 'Sunday', 'opens' => '', 'closes' => '', 'closed' => true],
            ],
            'services' => ['wholesale-coffee', 'barista-training', 'equipment-servicing', 'coffee-subscriptions'],
        ],
        [
            'title' => 'Millgate',
            'slug' => 'millgate',
            'summary' => 'A small shop on the high street in Netherfield, four miles out.',
            'image' => 'location-millgate.jpg',
            'body' => <<<HTML
<p>Millgate opened in 2022 and is deliberately simple: espresso, filter, a short list of pastries from the bakery three doors down, and whatever is on the retail shelf that week.</p>
<p>It is the quieter of the two shops and the better one to work from. There is a long bench along the window with sockets under it.</p>
HTML,
            'addressLine1' => '7 Millgate',
            'addressLine2' => '',
            'addressCity' => 'Netherfield',
            'addressPostcode' => 'AS9 2RT',
            'mapUrl' => 'https://www.openstreetmap.org/search?query=Netherfield',
            'openingHours' => [
                ['day' => 'Monday', 'opens' => '', 'closes' => '', 'closed' => true],
                ['day' => 'Tuesday', 'opens' => '8:00', 'closes' => '15:30', 'closed' => false],
                ['day' => 'Wednesday', 'opens' => '8:00', 'closes' => '15:30', 'closed' => false],
                ['day' => 'Thursday', 'opens' => '8:00', 'closes' => '15:30', 'closed' => false],
                ['day' => 'Friday', 'opens' => '8:00', 'closes' => '15:30', 'closed' => false],
                ['day' => 'Saturday', 'opens' => '9:00', 'closes' => '16:00', 'closed' => false],
                ['day' => 'Sunday', 'opens' => '9:00', 'closes' => '14:00', 'closed' => false],
            ],
            'services' => ['coffee-subscriptions', 'barista-training'],
        ],
    ],

    // Top-level services, each with optional children. Two levels, as the
    // section's maxLevels allows.
    'services' => [
        [
            'title' => 'Wholesale coffee',
            'slug' => 'wholesale-coffee',
            'icon' => 'truck',
            'priceFrom' => 8.50,
            'image' => 'service-wholesale.jpg',
            'summary' => 'Roasted to order, delivered weekly across the county, with the dial-in visit included.',
            'body' => <<<HTML
<p>We roast on Tuesdays and Wednesdays and deliver the same week, so what arrives is between two and six days off the roaster. Minimum order is five kilograms and there is no contract.</p>
<p>Every new wholesale account gets a dial-in visit before the first delivery and a follow-up a fortnight later. That second visit is the one that matters, because it is when the questions actually surface.</p>
HTML,
            'journal' => ['why-we-roast-to-order', 'reading-first-crack'],
            'children' => [
                [
                    'title' => 'Café supply',
                    'slug' => 'cafe-supply',
                    'icon' => 'mug-hot',
                    'priceFrom' => 8.50,
                    'summary' => 'Espresso and filter for cafés, with grinder support and staff training built in.',
                    'body' => '<p>House espresso, a rotating single origin, and a filter roast if you brew batch. Prices include the dial-in visits and a standing offer to come out when something goes wrong with the grind.</p>',
                ],
                [
                    'title' => 'Office supply',
                    'slug' => 'office-supply',
                    'icon' => 'building',
                    'priceFrom' => 11.00,
                    'summary' => 'Ground or whole bean on a standing order, sized to how much your office actually drinks.',
                    'body' => '<p>Most offices over-order coffee and then drink it stale. We start you on a fortnightly bag, look at what comes back, and adjust. Equipment hire is available if you have nothing better than a kettle.</p>',
                ],
            ],
        ],
        [
            'title' => 'Barista training',
            'slug' => 'barista-training',
            'icon' => 'graduation-cap',
            'priceFrom' => 120.00,
            'image' => 'service-training.jpg',
            'summary' => 'Half-day and full-day sessions at Arch 14, four people maximum.',
            'body' => <<<HTML
<p>Training runs in the roastery on Mondays, when the bar is quiet and the machine is free. Groups are capped at four so everybody gets real time on the machine rather than watching someone else have it.</p>
<p>Sessions are built around what you actually need. If your team already pulls decent shots and the problem is milk, we spend the day on milk.</p>
HTML,
            'journal' => ['milk-is-the-hard-part', 'grind-first-everything-else-second'],
            'children' => [
                [
                    'title' => 'Foundation skills',
                    'slug' => 'foundation-skills',
                    'icon' => 'seedling',
                    'priceFrom' => 120.00,
                    'summary' => 'A half day on grinding, dosing, extraction and the habits that keep a bar consistent.',
                    'body' => '<p>For people who are new behind a machine, or who learned on the job and never had anyone explain why. Finishes with everyone pulling and tasting their own shots against a target.</p>',
                ],
                [
                    'title' => 'Latte art',
                    'slug' => 'latte-art',
                    'icon' => 'heart',
                    'priceFrom' => 95.00,
                    'summary' => 'A half day on texture first, pattern second. Bring your own jug if you have a favourite.',
                    'body' => '<p>Almost every latte art problem is a milk texturing problem. We spend the first half of the session on steaming and only pick up patterns once the milk is right.</p>',
                ],
            ],
        ],
        [
            'title' => 'Equipment servicing',
            'slug' => 'equipment-servicing',
            'icon' => 'wrench',
            'priceFrom' => 95.00,
            'image' => 'service-servicing.jpg',
            'summary' => 'Scheduled servicing and callouts for espresso machines and grinders within thirty miles.',
            'body' => <<<HTML
<p>Gaskets, showers, burrs, water treatment and the annual pressure vessel check. We service what we sell and plenty of what we did not, as long as parts are still available for it.</p>
<p>Wholesale accounts get priority on callouts. Everyone else we fit in as soon as we can, usually within three working days.</p>
HTML,
            'journal' => ['water-is-the-other-ingredient'],
            'children' => [],
        ],
        [
            'title' => 'Coffee subscriptions',
            'slug' => 'coffee-subscriptions',
            'icon' => 'box',
            'priceFrom' => 9.00,
            'image' => 'service-subscriptions.jpg',
            'summary' => 'A bag at a time, as often as you drink it, posted or collected from either shop.',
            'body' => <<<HTML
<p>Pick a roast or let us choose. Collection from Arch 14 or Millgate is a pound cheaper and means the coffee is fresher, because it skips a day in the post.</p>
<p>Pause it whenever you like. We would rather you stopped for a month than ended up with four unopened bags in a cupboard.</p>
HTML,
            'journal' => ['seasonality-and-why-your-favourite-ran-out', 'the-case-for-good-decaf'],
            'children' => [],
        ],
    ],

    'journal' => [
        [
            'title' => 'Reading first crack',
            'slug' => 'reading-first-crack',
            'image' => 'journal-first-crack.jpg',
            'topics' => ['roasting'],
            'date' => '2026-09-18 09:00:00',
            'standfirst' => 'The noise coffee makes halfway through a roast tells you more than the thermocouple does, if you learn what to listen for.',
            'body' => <<<HTML
<p>Somewhere around 196°C, green coffee stops absorbing heat quietly and starts to come apart. Water trapped in the bean turns to steam, pressure builds, and the cell structure gives way with a sound like a pine log on a fire. That is first crack, and almost everything that makes a roast taste the way it does happens in the three or four minutes that follow.</p>
<h2>Why the thermometer lies</h2>
<p>A bean probe tells you the temperature of the bean mass, averaged, with a lag of several seconds. On a cold morning with a full drum, the same reading can mean two quite different things. The crack does not lag. It is the beans reporting on themselves.</p>
<p>What we listen for is not the first snap but the shape of the crowd. A roast that comes into crack raggedly, with a long tail of stragglers, has not developed evenly, and no amount of time afterwards fixes that.</p>
<h2>What we do with it</h2>
<p>Once crack is properly underway we mark the time and work in percentages rather than absolutes. Development time is the stretch between the start of crack and the drop, as a proportion of the whole roast. For our filter roasts that lands between 18 and 22 per cent. Below 18 the cup gets sharp and grassy. Above 22 it flattens out and starts tasting of the roast rather than the coffee.</p>
<p>None of this is exotic. It is just the discipline of writing down what you heard and when, roast after roast, until the numbers start predicting the cup.</p>
HTML,
        ],
        [
            'title' => 'Water is the other ingredient',
            'slug' => 'water-is-the-other-ingredient',
            'image' => 'journal-water.jpg',
            'topics' => ['brewing'],
            'date' => '2026-09-04 09:00:00',
            'standfirst' => 'A cup of coffee is about 98 per cent water. It is worth paying some attention to the 98 per cent.',
            'body' => <<<HTML
<p>People will spend forty pounds on a bag of competition lot and then brew it with water straight out of a hard-water tap. The coffee has almost no chance.</p>
<h2>What water actually does</h2>
<p>Two things matter. Hardness, meaning the magnesium and calcium that physically pull flavour compounds out of the grounds, and alkalinity, meaning the carbonate that buffers acidity. Too little hardness and the cup is thin and hollow. Too much alkalinity and everything bright in the coffee gets muted, which is why hard-water towns tend to prefer darker roasts without quite knowing why.</p>
<h2>What to do about it</h2>
<p>For filter at home, a simple jug filter is a real improvement over hard tap water and costs almost nothing. For espresso it matters more, because scale builds in the boiler and because the extraction window is narrower.</p>
<p>If you run a machine commercially, the honest answer is to get your supply tested once and specify treatment from the result rather than guessing. We do this as part of a servicing visit. It is unglamorous and it changes the cup more than most equipment upgrades.</p>
HTML,
        ],
        [
            'title' => 'Why we roast to order',
            'slug' => 'why-we-roast-to-order',
            'image' => 'journal-ethiopia.jpg',
            'topics' => ['roasting', 'origins'],
            'date' => '2026-08-21 09:00:00',
            'standfirst' => 'Holding stock would be easier for us and worse for you. Here is the arithmetic behind that.',
            'body' => <<<HTML
<p>Roasted coffee degrades from the moment it leaves the drum. Carbon dioxide escapes, oxygen gets in, and the aromatic compounds that make a coffee taste like a specific place rather than just like coffee start to go. In a sealed valve bag it is slow. On a shelf in a warm shop it is not.</p>
<h2>The window</h2>
<p>For filter, we think coffee is at its best between five and twenty-one days off roast. Espresso wants a little longer to settle, say seven to twenty-eight. Outside that it is not bad, it is just less itself.</p>
<p>Roasting to order means almost everything we send out is inside that window when it arrives. The cost is that we cannot fulfil a large order the same afternoon, and occasionally a popular roast is simply not available until Wednesday.</p>
<h2>What it means for wholesale</h2>
<p>Wholesale orders close on Monday morning and go out Thursday. Cafés that have worked with bigger roasters sometimes find this restrictive at first. In practice it takes about a month to settle into, and the gain in consistency is immediate, because every delivery is the same age rather than whatever happened to be in the warehouse.</p>
HTML,
        ],
        [
            'title' => 'Grind first, everything else second',
            'slug' => 'grind-first-everything-else-second',
            'image' => 'journal-grinder.jpg',
            'topics' => ['brewing'],
            'date' => '2026-08-07 09:00:00',
            'standfirst' => 'If you are going to spend money on one piece of equipment, it is not the machine.',
            'body' => <<<HTML
<p>We get asked about espresso machines constantly and about grinders almost never, which is the wrong way round. A good grinder on a modest machine will beat a poor grinder on an expensive one every time.</p>
<h2>Why</h2>
<p>Extraction depends on water passing evenly through the bed of coffee. A grinder that produces a wide spread of particle sizes gives the water easy routes through the large particles and slow routes through the small ones. You end up over-extracting the fines and under-extracting the boulders in the same shot, and the result tastes both bitter and sour at once.</p>
<p>Burr geometry, alignment and wear all feed into that spread. Burrs are consumable. On a busy bar they want replacing every six hundred kilos or so, and most places run them far longer because nothing obviously breaks.</p>
<h2>At home</h2>
<p>A hand grinder with decent burrs will out-perform most cheap electric ones. If you brew filter only, you do not need anything expensive. If you pull espresso, the grinder is where the money should go.</p>
HTML,
        ],
        [
            'title' => 'Milk is the hard part',
            'slug' => 'milk-is-the-hard-part',
            'image' => 'journal-milk.jpg',
            'topics' => ['brewing'],
            'date' => '2026-07-24 09:00:00',
            'standfirst' => 'Most bars we visit can pull a reasonable shot. Far fewer can steam milk consistently at eleven on a Saturday.',
            'body' => <<<HTML
<p>Espresso has a feedback loop. You can taste it, weigh it, time it, and adjust. Milk has almost none of that, which is why it is the skill that separates bars that are fine from bars that are good.</p>
<h2>Texture before pattern</h2>
<p>Latte art is a symptom, not a skill in itself. If the milk is right, the pattern is comparatively easy. If it is not, no amount of wrist technique rescues it. So we spend the first half of every training session on the sound of the steam wand and the feel of the jug, and we do not pour anything at all.</p>
<h2>The Saturday problem</h2>
<p>Technique that works at nine in the morning falls apart when there are fourteen drinks on the pass. The fix is rarely more training. It is usually jug management: enough jugs, rinsed properly, so nobody is steaming into milk residue, and a habit of steaming for the drink in front of you rather than optimistically for the next three.</p>
<p>Non-dairy alternatives behave differently enough that they want their own practice. Oat textures at a lower temperature than dairy and punishes you faster if you overshoot.</p>
HTML,
        ],
        [
            'title' => 'The case for good decaf',
            'slug' => 'the-case-for-good-decaf',
            'image' => 'journal-decaf.jpg',
            'topics' => ['roasting', 'origins'],
            'date' => '2026-07-10 09:00:00',
            'standfirst' => 'Decaf has been the afterthought on most coffee menus for thirty years. It does not have to be.',
            'body' => <<<HTML
<p>The old reputation was earned. For a long time decaffeination used solvents on poor-quality green coffee, and the result tasted like it. That is no longer the only option, and it has not been for some time.</p>
<h2>How ours is done</h2>
<p>We buy sugarcane process decaf from Colombia. Ethyl acetate derived from fermented cane juice is used to bond with and remove caffeine, and because the process is relatively gentle, more of the coffee's own character survives it. Swiss Water process is the other good option and we rotate onto it when the lot is right.</p>
<p>The important part is upstream of any of that: we buy green coffee for decaf that we would be happy to roast as regular coffee. Most of the industry does the opposite.</p>
<h2>Roasting it</h2>
<p>Decaffeinated beans are structurally different. They take on colour faster, so going by sight will burn them, and they come into first crack earlier and more quietly. We run a separate profile with a lower charge temperature and we never roast it immediately after a hot batch.</p>
<p>The test we care about is whether someone orders it twice. Increasingly they do.</p>
HTML,
        ],
        [
            'title' => 'Seasonality, and why your favourite ran out',
            'slug' => 'seasonality-and-why-your-favourite-ran-out',
            'image' => 'journal-seasonality.jpg',
            'topics' => ['origins'],
            'date' => '2026-06-26 09:00:00',
            'standfirst' => 'Coffee is an agricultural product with a harvest, which is obvious until the coffee you like disappears in March.',
            'body' => <<<HTML
<p>Every producing country has a harvest window. Ethiopia picks between October and January. Colombia has two. Brazil runs May to September. Green coffee arrives in the months after that, and it is at its best for roughly a year before it starts tasting flat and papery.</p>
<h2>What that means in practice</h2>
<p>A single-origin coffee on our shelf is a specific lot from a specific harvest. When it is gone, it is gone, and the same farm next year will taste recognisably related but not identical. Rainfall, picking decisions and processing all move.</p>
<p>We could avoid this by blending to a fixed profile all year, which is what large roasters do and what makes commercial sense. We mostly do not, because the whole point of buying this way is that the differences are interesting.</p>
<h2>What we do instead</h2>
<p>We tell subscribers a month before a favourite runs low and suggest what is closest in the new arrivals. The house espresso is the exception: that one we do hold steady through the year, adjusting the components so the cup stays where regulars expect it.</p>
HTML,
        ],
        [
            'title' => 'What a cupping table is for',
            'slug' => 'what-a-cupping-table-is-for',
            'image' => 'journal-cupping.jpg',
            'topics' => ['roasting', 'origins'],
            'date' => '2026-06-12 09:00:00',
            'standfirst' => 'Twelve bowls, boiling water, a spoon and no milk. It looks like a ritual and it is really a measuring instrument.',
            'body' => <<<HTML
<p>Cupping strips everything variable out of brewing so that the only thing left changing is the coffee. Same dose, same grind, same water, same time. Whatever differences you taste are real differences in the bean or the roast.</p>
<h2>How we use it</h2>
<p>Three times a week. Once on green samples we are considering buying, once on production roasts to check they match the profile, and once on whatever is open on the retail shelf to see how it is ageing.</p>
<p>The production cupping is the unglamorous one and the one that catches problems. A roast that drifted two per cent on development time is obvious on the table and invisible in the bag.</p>
<h2>Come to one</h2>
<p>We run an open cupping at Arch 14 on the last Saturday of the month. It is free, it takes an hour, and you do not need any experience. The only real skill is saying what you taste before you hear what anybody else tasted.</p>
HTML,
        ],
        [
            'title' => 'Thursdays at Arch 14',
            'slug' => 'thursdays-at-arch-14',
            'image' => 'journal-espresso.jpg',
            'topics' => ['the-shops'],
            'date' => '2026-05-29 09:00:00',
            'standfirst' => 'We are staying open until eight on Thursdays through the winter. Here is what that looks like.',
            'body' => <<<HTML
<p>The arch is warm, it is dark by half four, and the bar sits empty from five o'clock onwards. That seemed like a waste, so from this month Arch 14 stays open until eight on Thursdays.</p>
<h2>What is on</h2>
<p>Filter and espresso as usual, plus whatever the bakery has not sold. Once a month the open cupping moves to a Thursday evening rather than a Saturday morning, which suits people who work.</p>
<p>We are also using the time for the things that do not fit into a trading day: grinder clinics where you can bring in a hand grinder and have the burrs checked, and the occasional evening where a producer we buy from joins by video to talk about the harvest.</p>
<h2>Millgate</h2>
<p>Millgate keeps its normal hours. It is a smaller team and the high street empties earlier, so late opening there would be optimistic.</p>
HTML,
        ],
    ],
];
