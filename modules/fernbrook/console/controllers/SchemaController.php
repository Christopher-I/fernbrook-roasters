<?php

namespace modules\fernbrook\console\controllers;

use Craft;
use craft\base\FieldInterface;
use craft\console\Controller;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\conditions\entries\EntryCondition;
use craft\elements\conditions\ElementCondition;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\User;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\assets\AssetTitleField;
use craft\fieldlayoutelements\TitleField;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\fieldlayoutelements\users\EmailField;
use craft\fieldlayoutelements\users\FullNameField;
use craft\fieldlayoutelements\users\PhotoField;
use craft\fieldlayoutelements\users\UsernameField;
use craft\fields\Assets;
use craft\fields\Categories;
use craft\fields\conditions\LightswitchFieldConditionRule;
use craft\fields\Dropdown;
use craft\fields\Email;
use craft\fields\Entries;
use craft\fields\Icon;
use craft\fields\Lightswitch;
use craft\fields\Link;
use craft\fields\Matrix;
use craft\fields\Money;
use craft\fields\PlainText;
use craft\fields\Table;
use craft\fields\Users;
use craft\fs\Local;
use craft\mail\transportadapters\Smtp;
use craft\models\CategoryGroup;
use craft\models\CategoryGroup_SiteSettings;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\ImageTransform;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\models\Volume;
use ReflectionClass;
use RuntimeException;
use yii\console\ExitCode;

/**
 * Builds the Fernbrook Roasters content model.
 *
 * Everything the site needs - filesystems, image transforms, fields, entry
 * types, sections, categories and globals - is defined here rather than
 * clicked together in the control panel. Craft writes the result to
 * config/project/, which is what actually ships; this command is how that
 * YAML gets authored and how the whole model can be rebuilt from nothing.
 *
 * Safe to run repeatedly: every component is looked up by handle first and
 * updated in place if it already exists.
 *
 *     php craft fernbrook/schema/build
 */
class SchemaController extends Controller
{
    /** @var FieldInterface[] */
    private array $fields = [];

    public function actionBuild(): int
    {
        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $this->stderr("allowAdminChanges is off. Run this in the dev environment.\n");
            return ExitCode::CONFIG;
        }

        $this->stdout("Building the Fernbrook Roasters content model\n\n");

        $this->buildFilesystemsAndVolumes();
        $this->buildTransforms();
        $this->buildCategoryGroup();
        $this->buildFields();
        $this->buildMatrixField();
        $this->buildSections();
        $this->buildGlobals();
        $this->buildUserLayout();
        $this->buildMailSettings();

        $this->stdout("\nDone. Review config/project/ and commit it.\n");

        return ExitCode::OK;
    }

    // -----------------------------------------------------------------
    // Assets
    // -----------------------------------------------------------------

    private function buildFilesystemsAndVolumes(): void
    {
        $this->stdout("Filesystems and volumes\n");

        $fsService = Craft::$app->getFs();

        foreach ([
            ['handle' => 'images', 'name' => 'Images', 'subpath' => 'images'],
            ['handle' => 'documents', 'name' => 'Documents', 'subpath' => 'documents'],
        ] as $spec) {
            $fs = $fsService->getFilesystemByHandle($spec['handle']);

            if (!$fs instanceof Local) {
                $fs = new Local();
            }

            $fs->name = $spec['name'];
            $fs->handle = $spec['handle'];
            $fs->hasUrls = true;
            // Resolved at runtime so the same project config works locally and
            // in production, where uploads live on a mounted volume.
            $fs->url = '@web/uploads/' . $spec['subpath'];
            $fs->path = '@webroot/uploads/' . $spec['subpath'];

            if (!$fsService->saveFilesystem($fs)) {
                throw new RuntimeException("Filesystem {$spec['handle']}: " . $this->errors($fs));
            }

            $this->stdout("  fs {$spec['handle']}\n");
        }

        $altField = $this->plainText('assetCaption', 'Caption', [
            'instructions' => 'Shown beneath the image where the template supports it.',
            'multiline' => true,
            'initialRows' => 2,
        ]);

        $creditField = $this->plainText('assetCredit', 'Credit', [
            'instructions' => 'Photographer or source, if one needs crediting.',
        ]);

        $volumesService = Craft::$app->getVolumes();

        foreach ([
            ['handle' => 'images', 'name' => 'Images', 'fs' => 'images', 'withFields' => true],
            ['handle' => 'documents', 'name' => 'Documents', 'fs' => 'documents', 'withFields' => false],
        ] as $spec) {
            $volume = $volumesService->getVolumeByHandle($spec['handle']) ?? new Volume();
            $volume->name = $spec['name'];
            $volume->handle = $spec['handle'];
            $volume->setFsHandle($spec['fs']);
            $volume->altTranslationMethod = 'site';

            // Craft injects the native Title element if a layout does not
            // declare one, with a new UUID each time. Declaring it keeps the
            // project config stable across rebuilds.
            $elements = [new AssetTitleField()];

            if ($spec['withFields']) {
                $elements[] = new CustomField($altField);
                $elements[] = new CustomField($creditField);
            }

            $volume->setFieldLayout($this->layout("volume.{$spec['handle']}", Asset::class, [
                'Content' => $elements,
            ], $volume->getFieldLayout()));

            if (!$volumesService->saveVolume($volume)) {
                throw new RuntimeException("Volume {$spec['handle']}: " . $this->errors($volume));
            }

            $this->stdout("  volume {$spec['handle']}\n");
        }
    }

    /**
     * Named transforms. Templates reference these by handle rather than
     * passing dimensions inline, so the sizes can be retuned in one place
     * without touching Twig.
     */
    private function buildTransforms(): void
    {
        $this->stdout("Image transforms\n");

        $service = Craft::$app->getImageTransforms();

        $specs = [
            ['handle' => 'hero', 'name' => 'Hero', 'width' => 1600, 'height' => 900],
            ['handle' => 'card', 'name' => 'Card', 'width' => 800, 'height' => 600],
            ['handle' => 'thumb', 'name' => 'Thumbnail', 'width' => 400, 'height' => 400],
        ];

        foreach ($specs as $spec) {
            $transform = $service->getTransformByHandle($spec['handle']) ?? new ImageTransform();
            $transform->name = $spec['name'];
            $transform->handle = $spec['handle'];
            $transform->width = $spec['width'];
            $transform->height = $spec['height'];
            $transform->mode = 'crop';
            $transform->position = 'center-center';
            $transform->format = 'webp';
            $transform->quality = 82;
            $transform->interlace = 'line';
            $transform->upscale = false;

            if (!$service->saveTransform($transform)) {
                throw new RuntimeException("Transform {$spec['handle']}: " . $this->errors($transform));
            }

            $this->stdout("  transform {$spec['handle']}\n");
        }
    }

    private function buildCategoryGroup(): void
    {
        $this->stdout("Categories\n");

        $service = Craft::$app->getCategories();
        $group = $service->getGroupByHandle('journalTopics') ?? new CategoryGroup();
        $group->name = 'Journal Topics';
        $group->handle = 'journalTopics';
        $group->maxLevels = 1;

        $siteSettings = [];
        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $siteSettings[$site->id] = new CategoryGroup_SiteSettings([
                'siteId' => $site->id,
                'hasUrls' => true,
                'uriFormat' => 'journal/topic/{slug}',
                'template' => '_journal/topic',
            ]);
        }
        $group->setSiteSettings($siteSettings);

        $group->setFieldLayout($this->layout('categoryGroup.journalTopics', Category::class, [
            'Content' => [
                new TitleField(),
                new CustomField($this->plainText('summary', 'Summary', [
                    'instructions' => 'One or two sentences. Used on listing pages and in search results.',
                    'multiline' => true,
                    'initialRows' => 3,
                    'searchable' => true,
                ])),
            ],
        ], $group->getFieldLayout()));

        if (!$service->saveGroup($group)) {
            throw new RuntimeException('Category group: ' . $this->errors($group));
        }

        $this->stdout("  group journalTopics\n");
    }

    // -----------------------------------------------------------------
    // Fields
    // -----------------------------------------------------------------

    private function buildFields(): void
    {
        $this->stdout("Fields\n");

        $this->plainText('summary', 'Summary', [
            'instructions' => 'One or two sentences. Used on listing pages and in search results.',
            'multiline' => true,
            'initialRows' => 3,
            'searchable' => true,
        ]);

        $this->plainText('standfirst', 'Standfirst', [
            'instructions' => 'The introductory paragraph, set larger than the body copy.',
            'multiline' => true,
            'initialRows' => 3,
            'searchable' => true,
        ]);

        $this->richText('bodyText', 'Body');
        $this->richText('columnLeft', 'Left column');
        $this->richText('columnRight', 'Right column');

        $this->image('heroImage', 'Hero image', 'The wide image at the top of the page.');
        $this->image('blockImage', 'Image', 'Shown full width unless a narrower width is chosen.');

        $this->plainText('imageCaption', 'Caption', ['multiline' => true, 'initialRows' => 2]);

        $this->dropdown('imageWidth', 'Width', [
            ['label' => 'Inset', 'value' => 'inset', 'default' => true],
            ['label' => 'Full width', 'value' => 'full'],
            ['label' => 'Break out', 'value' => 'wide'],
        ]);

        $this->plainText('ctaHeading', 'Heading', ['searchable' => true]);
        $this->plainText('ctaText', 'Text', ['multiline' => true, 'initialRows' => 3]);
        $this->lightswitch('showButton', 'Show a button', 'Turn this on to reveal the button settings.', true);
        $this->plainText('buttonLabel', 'Button label');
        $this->link('buttonLink', 'Button links to');

        $this->plainText('quoteText', 'Quote', [
            'multiline' => true,
            'initialRows' => 3,
            'searchable' => true,
        ]);
        $this->plainText('quoteAttribution', 'Attribution');

        $this->plainText('formHeading', 'Heading');
        $this->plainText('formIntro', 'Introduction', ['multiline' => true, 'initialRows' => 3]);

        $this->field(Users::class, 'articleAuthor', [
            'name' => 'Author',
            'instructions' => 'Whose byline appears on this article.',
            'maxRelations' => 1,
            'selectionLabel' => 'Choose an author',
        ]);

        $this->field(Categories::class, 'journalTopics', [
            'name' => 'Topics',
            'source' => 'group:' . Craft::$app->getCategories()->getGroupByHandle('journalTopics')->uid,
            'selectionLabel' => 'Add a topic',
        ]);

        $this->field(Money::class, 'priceFrom', [
            'name' => 'Price from',
            'instructions' => 'Leave empty for services that are quoted individually.',
            'currency' => 'GBP',
            'min' => 0,
        ]);

        $this->field(Icon::class, 'serviceIcon', [
            'name' => 'Icon',
            'instructions' => 'Shown on service cards and in the services navigation.',
        ]);

        $this->plainText('addressLine1', 'Address line 1', ['searchable' => true]);
        $this->plainText('addressLine2', 'Address line 2');
        $this->plainText('addressCity', 'Town or city', ['searchable' => true]);
        $this->plainText('addressPostcode', 'Postcode', ['searchable' => true]);

        $this->field(Link::class, 'mapUrl', [
            'name' => 'Map link',
            'instructions' => 'A link to this address on a maps service.',
            'types' => ['url'],
            'showLabelField' => false,
        ]);

        $this->field(Table::class, 'openingHours', [
            'name' => 'Opening hours',
            'addRowLabel' => 'Add a day',
            'minRows' => 1,
            'maxRows' => 7,
            'columns' => [
                'col1' => ['heading' => 'Day', 'handle' => 'day', 'type' => 'singleline', 'width' => '30%'],
                'col2' => ['heading' => 'Opens', 'handle' => 'opens', 'type' => 'singleline'],
                'col3' => ['heading' => 'Closes', 'handle' => 'closes', 'type' => 'singleline'],
                'col4' => ['heading' => 'Closed all day', 'handle' => 'closed', 'type' => 'lightswitch'],
            ],
            'defaults' => [],
        ]);

        $this->plainText('contactPhone', 'Phone');
        $this->field(Email::class, 'contactEmail', ['name' => 'Email']);
        $this->plainText('postalAddress', 'Postal address', ['multiline' => true, 'initialRows' => 4]);

        $this->field(Table::class, 'socialLinks', [
            'name' => 'Social links',
            'addRowLabel' => 'Add a link',
            'columns' => [
                'col1' => ['heading' => 'Network', 'handle' => 'network', 'type' => 'singleline', 'width' => '30%'],
                'col2' => ['heading' => 'URL', 'handle' => 'url', 'type' => 'url'],
            ],
            'defaults' => [],
        ]);

        $this->lightswitch('announcementEnabled', 'Show the announcement bar', 'Turn this on to show a strip across the top of every page.');
        $this->plainText('announcementText', 'Announcement', ['multiline' => true, 'initialRows' => 2]);
        $this->link('announcementLink', 'Announcement links to');

        $this->plainText('seoTitle', 'Search engine title', [
            'instructions' => 'Overrides the page title in search results. Leave empty to use the entry title.',
        ]);
        $this->plainText('seoDescription', 'Search engine description', [
            'instructions' => 'Around 150 characters. Shown underneath the title in search results.',
            'multiline' => true,
            'initialRows' => 3,
            'charLimit' => 200,
        ]);
        $this->lightswitch('hideFromNav', 'Hide from the main navigation', 'The page stays published and reachable by its URL.');

        $this->plainText('userRole', 'Role', ['instructions' => 'Shown under the name on article bylines.']);
        $this->plainText('userBio', 'Bio', ['multiline' => true, 'initialRows' => 4]);

        $this->field(Entries::class, 'relatedJournal', [
            'name' => 'Related journal entries',
            'instructions' => 'Pulled onto the service page as further reading.',
            'sources' => ['section:' . ($this->sectionUid('journal') ?? '')],
            'maxRelations' => 4,
            'selectionLabel' => 'Add an article',
        ]);

        $this->field(Entries::class, 'servicesOffered', [
            'name' => 'Services offered here',
            'sources' => ['section:' . ($this->sectionUid('services') ?? '')],
            'selectionLabel' => 'Add a service',
        ]);
    }

    /**
     * The page builder: one Matrix field whose block types are entry types in
     * their own right, which is how Craft 5 models Matrix content.
     */
    private function buildMatrixField(): void
    {
        $this->stdout("Matrix block types\n");

        $blockTypes = [];

        $blockTypes[] = $this->entryType('blockRichText', 'Text', [
            'hasTitleField' => false,
            'titleFormat' => 'Text',
            'icon' => 'align-left',
            'tabs' => [
                'Content' => [new CustomField($this->fields['bodyText'])],
            ],
        ]);

        $blockTypes[] = $this->entryType('blockImage', 'Image', [
            'hasTitleField' => false,
            'titleFormat' => 'Image',
            'icon' => 'image',
            'tabs' => [
                'Content' => [
                    new CustomField($this->fields['blockImage'], ['required' => true]),
                    new CustomField($this->fields['imageCaption']),
                    new CustomField($this->fields['imageWidth']),
                ],
            ],
        ]);

        $blockTypes[] = $this->entryType('blockTwoColumn', 'Two columns', [
            'hasTitleField' => false,
            'titleFormat' => 'Two columns',
            'icon' => 'table-columns',
            'tabs' => [
                'Content' => [
                    new CustomField($this->fields['columnLeft'], ['width' => 50]),
                    new CustomField($this->fields['columnRight'], ['width' => 50]),
                ],
            ],
        ]);

        // The conditional-field example. buttonLabel and buttonLink only
        // appear once the editor turns "Show a button" on, so the block's
        // settings never show fields that would have no effect.
        $buttonCondition = [
            'class' => EntryCondition::class,
            // Without an explicit element type the condition cannot see the
            // layout's custom fields, and the rule is silently dropped.
            'elementType' => Entry::class,
            'conditionRules' => [
                [
                    'class' => LightswitchFieldConditionRule::class,
                    'fieldUid' => $this->fields['showButton']->uid,
                    'uid' => $this->stableUid('condition.blockCta.showButton'),
                    'value' => true,
                ],
            ],
        ];

        $buttonLabel = new CustomField($this->fields['buttonLabel'], ['required' => true, 'width' => 50]);
        $buttonLabel->setElementCondition($buttonCondition);

        $buttonLink = new CustomField($this->fields['buttonLink'], ['required' => true, 'width' => 50]);
        $buttonLink->setElementCondition($buttonCondition);

        $blockTypes[] = $this->entryType('blockCta', 'Call to action', [
            'hasTitleField' => false,
            'titleFormat' => 'Call to action',
            'icon' => 'bullhorn',
            'tabs' => [
                'Content' => [
                    new CustomField($this->fields['ctaHeading'], ['required' => true]),
                    new CustomField($this->fields['ctaText']),
                    new CustomField($this->fields['showButton']),
                    $buttonLabel,
                    $buttonLink,
                ],
            ],
        ]);

        $blockTypes[] = $this->entryType('blockPullQuote', 'Pull quote', [
            'hasTitleField' => false,
            'titleFormat' => 'Pull quote',
            'icon' => 'quote-left',
            'tabs' => [
                'Content' => [
                    new CustomField($this->fields['quoteText'], ['required' => true]),
                    new CustomField($this->fields['quoteAttribution']),
                ],
            ],
        ]);

        $blockTypes[] = $this->entryType('blockForm', 'Contact form', [
            'hasTitleField' => false,
            'titleFormat' => 'Contact form',
            'icon' => 'envelope',
            'tabs' => [
                'Content' => [
                    new CustomField($this->fields['formHeading']),
                    new CustomField($this->fields['formIntro']),
                ],
            ],
        ]);

        $this->field(Matrix::class, 'pageBuilder', [
            'name' => 'Page content',
            'instructions' => 'Build the page from blocks. Drag to reorder.',
            'viewMode' => Matrix::VIEW_MODE_BLOCKS,
            'entryTypes' => $blockTypes,
            'searchable' => true,
        ]);

        $this->stdout("  field pageBuilder\n");
    }

    // -----------------------------------------------------------------
    // Sections
    // -----------------------------------------------------------------

    private function buildSections(): void
    {
        $this->stdout("Sections\n");

        $seoTab = [
            new CustomField($this->fields['seoTitle']),
            new CustomField($this->fields['seoDescription']),
        ];

        // Pages -------------------------------------------------------
        $pageType = $this->entryType('page', 'Page', [
            'icon' => 'file-lines',
            'tabs' => [
                'Content' => [
                    new CustomField($this->fields['summary']),
                    new CustomField($this->fields['heroImage']),
                    new CustomField($this->fields['pageBuilder']),
                ],
                'Navigation' => [
                    new CustomField($this->fields['hideFromNav']),
                ],
                'SEO' => $seoTab,
            ],
        ]);

        $this->section('pages', 'Pages', Section::TYPE_STRUCTURE, [$pageType], [
            'maxLevels' => 3,
            // Craft treats the "__home__" URI as the site root, so the home
            // page is an ordinary Page entry and gets live preview like the
            // rest of them.
            'uriFormat' => "{% if slug == 'home' %}__home__{% elseif level == 1 %}{slug}{% else %}{parent.uri}/{slug}{% endif %}",
            'template' => '_pages/entry',
            'previewTargets' => [['label' => 'Page', 'urlFormat' => '{url}']],
        ]);

        // Journal -----------------------------------------------------
        $journalType = $this->entryType('journalEntry', 'Article', [
            'icon' => 'newspaper',
            'tabs' => [
                'Content' => [
                    new CustomField($this->fields['standfirst']),
                    new CustomField($this->fields['heroImage'], ['required' => true]),
                    new CustomField($this->fields['bodyText'], ['required' => true]),
                ],
                'Meta' => [
                    new CustomField($this->fields['articleAuthor'], ['required' => true, 'width' => 50]),
                    new CustomField($this->fields['journalTopics'], ['width' => 50]),
                ],
                'SEO' => $seoTab,
            ],
        ]);

        $this->section('journal', 'Journal', Section::TYPE_CHANNEL, [$journalType], [
            'uriFormat' => 'journal/{slug}',
            'template' => '_journal/entry',
            'previewTargets' => [['label' => 'Article', 'urlFormat' => '{url}'], ['label' => 'Journal index', 'urlFormat' => 'journal']],
        ]);

        // Services ----------------------------------------------------
        $serviceType = $this->entryType('service', 'Service', [
            'icon' => 'mug-hot',
            'tabs' => [
                'Content' => [
                    new CustomField($this->fields['summary'], ['required' => true]),
                    new CustomField($this->fields['serviceIcon'], ['width' => 50]),
                    new CustomField($this->fields['heroImage'], ['width' => 50]),
                    new CustomField($this->fields['bodyText']),
                ],
                'Commercial' => [
                    new CustomField($this->fields['priceFrom'], ['width' => 50]),
                    new CustomField($this->fields['relatedJournal']),
                ],
                'SEO' => $seoTab,
            ],
        ]);

        $this->section('services', 'Services', Section::TYPE_STRUCTURE, [$serviceType], [
            'maxLevels' => 2,
            'uriFormat' => "{% if level == 1 %}services/{slug}{% else %}{parent.uri}/{slug}{% endif %}",
            'template' => '_services/entry',
            'previewTargets' => [['label' => 'Service page', 'urlFormat' => '{url}'], ['label' => 'Services index', 'urlFormat' => 'services']],
        ]);

        // Locations ---------------------------------------------------
        $locationType = $this->entryType('location', 'Location', [
            'icon' => 'location-dot',
            'tabs' => [
                'Content' => [
                    new CustomField($this->fields['summary'], ['required' => true]),
                    new CustomField($this->fields['heroImage'], ['required' => true]),
                    new CustomField($this->fields['bodyText']),
                ],
                'Visiting' => [
                    new CustomField($this->fields['addressLine1'], ['required' => true, 'width' => 50]),
                    new CustomField($this->fields['addressLine2'], ['width' => 50]),
                    new CustomField($this->fields['addressCity'], ['required' => true, 'width' => 50]),
                    new CustomField($this->fields['addressPostcode'], ['required' => true, 'width' => 50]),
                    new CustomField($this->fields['mapUrl']),
                    new CustomField($this->fields['openingHours']),
                    new CustomField($this->fields['servicesOffered']),
                ],
                'SEO' => $seoTab,
            ],
        ]);

        $this->section('locations', 'Locations', Section::TYPE_CHANNEL, [$locationType], [
            'uriFormat' => 'visit/{slug}',
            'template' => '_locations/entry',
            'previewTargets' => [['label' => 'Location page', 'urlFormat' => '{url}'], ['label' => 'Visit index', 'urlFormat' => 'visit']],
        ]);

        // relatedJournal and servicesOffered are created before the sections
        // they point at exist, so their sources are filled in here.
        $this->field(Entries::class, 'relatedJournal', [
            'name' => 'Related journal entries',
            'instructions' => 'Pulled onto the service page as further reading.',
            'sources' => ['section:' . $this->sectionUid('journal')],
            'maxRelations' => 4,
            'selectionLabel' => 'Add an article',
        ]);

        $this->field(Entries::class, 'servicesOffered', [
            'name' => 'Services offered here',
            'sources' => ['section:' . $this->sectionUid('services')],
            'selectionLabel' => 'Add a service',
        ]);
    }

    private function buildGlobals(): void
    {
        $this->stdout("Globals\n");

        $service = Craft::$app->getGlobals();
        $set = $service->getSetByHandle('siteSettings') ?? new GlobalSet();
        $set->name = 'Site Settings';
        $set->handle = 'siteSettings';

        $announcementCondition = [
            'class' => ElementCondition::class,
            'elementType' => GlobalSet::class,
            'conditionRules' => [
                [
                    'class' => LightswitchFieldConditionRule::class,
                    'fieldUid' => $this->fields['announcementEnabled']->uid,
                    'uid' => $this->stableUid('condition.siteSettings.announcementEnabled'),
                    'value' => true,
                ],
            ],
        ];

        $announcementText = new CustomField($this->fields['announcementText'], ['required' => true]);
        $announcementText->setElementCondition($announcementCondition);

        $announcementLink = new CustomField($this->fields['announcementLink']);
        $announcementLink->setElementCondition($announcementCondition);

        $set->setFieldLayout($this->layout('globalSet.siteSettings', GlobalSet::class, [
            'Contact' => [
                new CustomField($this->fields['contactPhone'], ['width' => 50]),
                new CustomField($this->fields['contactEmail'], ['width' => 50]),
                new CustomField($this->fields['postalAddress']),
                new CustomField($this->fields['socialLinks']),
            ],
            'Announcement bar' => [
                new CustomField($this->fields['announcementEnabled']),
                $announcementText,
                $announcementLink,
            ],
        ], $set->getFieldLayout()));

        if (!$service->saveSet($set)) {
            throw new RuntimeException('Global set: ' . $this->errors($set));
        }

        $this->stdout("  global set siteSettings\n");
    }

    private function buildUserLayout(): void
    {
        $this->stdout("Users\n");

        $layout = $this->layout('user', User::class, [
            'Account' => [
                new UsernameField(),
                new FullNameField(),
                new PhotoField(),
                new EmailField(),
            ],
            'Profile' => [
                new CustomField($this->fields['userRole']),
                new CustomField($this->fields['userBio']),
            ],
        ], Craft::$app->getFields()->getLayoutByType(User::class));

        $layout->type = User::class;

        if (!Craft::$app->getUsers()->saveLayout($layout)) {
            throw new RuntimeException('User field layout: ' . $this->errors($layout));
        }

        // Craft needs somewhere to put user photos before one can be set.
        $projectConfig = Craft::$app->getProjectConfig();
        $projectConfig->set('users.photoVolumeUid', Craft::$app->getVolumes()->getVolumeByHandle('images')->uid);
        $projectConfig->set('users.photoSubpath', 'people');

        $this->stdout("  user field layout and photo volume\n");
    }

    /**
     * Mail goes through SMTP in every environment: Mailpit locally, a
     * transactional provider in production. The credentials stay in .env, so
     * only the variable names reach project config.
     */
    private function buildMailSettings(): void
    {
        $this->stdout("Mail\n");

        $projectConfig = Craft::$app->getProjectConfig();

        $projectConfig->set('email.fromEmail', '$MAIL_FROM_EMAIL');
        $projectConfig->set('email.fromName', '$MAIL_FROM_NAME');
        $projectConfig->set('email.replyToEmail', '$MAIL_FROM_EMAIL');
        $projectConfig->set('email.transportType', Smtp::class);
        $projectConfig->set('email.transportSettings', [
            'host' => '$MAIL_HOST',
            'port' => '$MAIL_PORT',
            'useAuthentication' => '$MAIL_USE_AUTH',
            'username' => '$MAIL_USERNAME',
            'password' => '$MAIL_PASSWORD',
        ]);

        $this->stdout("  SMTP transport\n");
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function field(string $class, string $handle, array $config): FieldInterface
    {
        $service = Craft::$app->getFields();
        $existing = $service->getFieldByHandle($handle);

        $config['handle'] = $handle;
        $config['name'] ??= $handle;

        if ($existing instanceof $class) {
            $field = $existing;
            Craft::configure($field, $config);
        } else {
            $field = new $class($config);

            // The handle is taken by a field of a different type. Reuse its
            // identity so Craft converts it rather than rejecting the save.
            if ($existing !== null) {
                $field->id = $existing->id;
                $field->uid = $existing->uid;
            }
        }

        if (!$service->saveField($field)) {
            throw new RuntimeException("Field $handle: " . $this->errors($field));
        }

        $this->fields[$handle] = $field;

        return $field;
    }

    private function plainText(string $handle, string $name, array $config = []): FieldInterface
    {
        return $this->field(PlainText::class, $handle, $config + ['name' => $name]);
    }

    private function richText(string $handle, string $name): FieldInterface
    {
        return $this->field(\craft\ckeditor\Field::class, $handle, [
            'name' => $name,
            'searchable' => true,
            'availableTransforms' => '*',
            'defaultTransform' => Craft::$app->getImageTransforms()->getTransformByHandle('hero')?->uid,
            'toolbar' => [
                'heading', '|', 'bold', 'italic', 'link', '|',
                'bulletedList', 'numberedList', 'blockQuote', '|',
                'insertImage', 'insertTable', '|', 'sourceEditing',
            ],
            'headingLevels' => [2, 3, 4],
        ]);
    }

    private function image(string $handle, string $name, string $instructions): FieldInterface
    {
        return $this->field(Assets::class, $handle, [
            'name' => $name,
            'instructions' => $instructions,
            'maxRelations' => 1,
            'restrictFiles' => true,
            'allowedKinds' => ['image'],
            'restrictLocation' => true,
            'restrictedLocationSource' => 'volume:' . Craft::$app->getVolumes()->getVolumeByHandle('images')->uid,
            'allowSubfolders' => true,
            'selectionLabel' => 'Choose an image',
            'viewMode' => 'large',
        ]);
    }

    private function dropdown(string $handle, string $name, array $options): FieldInterface
    {
        return $this->field(Dropdown::class, $handle, [
            'name' => $name,
            'options' => $options,
        ]);
    }

    private function lightswitch(string $handle, string $name, string $instructions = '', bool $default = false): FieldInterface
    {
        return $this->field(Lightswitch::class, $handle, [
            'name' => $name,
            'instructions' => $instructions,
            'default' => $default,
        ]);
    }

    private function link(string $handle, string $name): FieldInterface
    {
        return $this->field(Link::class, $handle, [
            'name' => $name,
            'types' => ['entry', 'url', 'email', 'tel'],
            'showLabelField' => false,
            // Root-relative URLs let an editor point at a listing page such
            // as /journal, which has a template but no entry behind it.
            'typeSettings' => [
                'url' => [
                    'allowRootRelativeUrls' => true,
                    'allowAnchors' => true,
                ],
            ],
        ]);
    }

    private function entryType(string $handle, string $name, array $config): EntryType
    {
        $service = Craft::$app->getEntries();
        $entryType = $service->getEntryTypeByHandle($handle) ?? new EntryType();

        $entryType->name = $name;
        $entryType->handle = $handle;
        $entryType->hasTitleField = $config['hasTitleField'] ?? true;
        $entryType->titleFormat = $config['titleFormat'] ?? null;
        $entryType->showSlugField = $config['showSlugField'] ?? ($config['hasTitleField'] ?? true);
        $entryType->icon = $config['icon'] ?? null;

        $tabs = $config['tabs'];

        // In Craft 5 the Title field is a field layout element, and an entry
        // type only counts as having a title field if its layout contains
        // one. Matrix block types opt out via hasTitleField => false.
        if ($entryType->hasTitleField) {
            $firstTab = array_key_first($tabs);
            $tabs[$firstTab] = array_merge([new EntryTitleField()], $tabs[$firstTab]);
        }

        $entryType->setFieldLayout($this->layout(
            "entryType.$handle",
            Entry::class,
            $tabs,
            $entryType->getFieldLayout(),
        ));

        if (!$service->saveEntryType($entryType)) {
            throw new RuntimeException("Entry type $handle: " . $this->errors($entryType));
        }

        $this->stdout("  entry type $handle\n");

        return $entryType;
    }

    /**
     * @param EntryType[] $entryTypes
     */
    private function section(string $handle, string $name, string $type, array $entryTypes, array $config): Section
    {
        $service = Craft::$app->getEntries();
        $section = $service->getSectionByHandle($handle) ?? new Section();

        $section->name = $name;
        $section->handle = $handle;
        $section->type = $type;
        $section->maxLevels = $config['maxLevels'] ?? null;
        $section->enableVersioning = true;
        // Live Preview targets. The first is the entry's own page; the second,
        // where there is one, is the listing it appears on, so an editor can
        // check the card as well as the page without leaving the editor.
        $section->previewTargets = array_map(
            fn(array $target) => $target + ['refresh' => true],
            $config['previewTargets'],
        );

        $siteSettings = [];
        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $siteSettings[$site->id] = new Section_SiteSettings([
                'siteId' => $site->id,
                'enabledByDefault' => true,
                'hasUrls' => true,
                'uriFormat' => $config['uriFormat'],
                'template' => $config['template'],
            ]);
        }
        $section->setSiteSettings($siteSettings);
        $section->setEntryTypes($entryTypes);

        if (!$service->saveSection($section)) {
            throw new RuntimeException("Section $handle: " . $this->errors($section));
        }

        $this->stdout("  section $handle\n");

        return $section;
    }

    /**
     * Builds a field layout from a map of tab name to layout elements.
     *
     * Craft 5 keys an element's stored content by the UUID of the *field
     * layout element*, not of the field. A layout element built fresh on
     * every run would get a fresh UUID, and every value already entered
     * against the old one would be orphaned. So each element and tab gets a
     * UUID derived from the owner and the field handle: rebuilding the model
     * on top of existing content leaves that content attached.
     *
     * @param array<string, array> $tabs
     */
    private function layout(string $ownerKey, string $elementType, array $tabs, ?FieldLayout $existing = null): FieldLayout
    {
        $layout = new FieldLayout(['type' => $elementType]);

        // Reuse the existing layout's identity so repeat runs update it in
        // place instead of orphaning the old one.
        if ($existing) {
            $layout->id = $existing->id;
            $layout->uid = $existing->uid;
        }

        $sortOrder = 1;
        $layoutTabs = [];

        foreach ($tabs as $tabName => $elements) {
            foreach ($elements as $element) {
                $element->uid = $this->stableUid(sprintf(
                    '%s.%s',
                    $ownerKey,
                    $element instanceof CustomField
                        ? 'field.' . $element->getField()->handle
                        : 'native.' . (new ReflectionClass($element))->getShortName(),
                ));
            }

            $tab = new FieldLayoutTab([
                'name' => $tabName,
                'sortOrder' => $sortOrder++,
                'uid' => $this->stableUid("$ownerKey.tab.$tabName"),
            ]);
            // The tab needs to know its layout before elements are attached.
            $tab->setLayout($layout);
            $tab->setElements($elements);
            $layoutTabs[] = $tab;
        }

        $layout->setTabs($layoutTabs);

        return $layout;
    }

    /**
     * A stable, well-formed UUID derived from a string, so the same input
     * always produces the same identifier.
     */
    private function stableUid(string $seed): string
    {
        $hash = md5('fernbrook-roasters:' . $seed);

        return sprintf(
            '%s-%s-5%s-8%s-%s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 13, 3),
            substr($hash, 17, 3),
            substr($hash, 20, 12),
        );
    }

    private function sectionUid(string $handle): ?string
    {
        return Craft::$app->getEntries()->getSectionByHandle($handle)?->uid;
    }

    private function errors(object $model): string
    {
        /** @phpstan-ignore-next-line */
        return json_encode($model->getErrors(), JSON_PRETTY_PRINT);
    }
}
