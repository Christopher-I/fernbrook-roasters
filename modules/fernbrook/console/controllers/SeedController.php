<?php

namespace modules\fernbrook\console\controllers;

use Craft;
use craft\console\Controller;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\User;
use craft\errors\InvalidElementException;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use DateTime;
use RuntimeException;
use yii\console\ExitCode;

/**
 * Seeds the demo content.
 *
 * The copy lives in seed/content.php and seed/pages.php, the images in
 * seed/images/ with their sources and licences in seed/images/credits.json.
 * Every image is Creative Commons or public domain, and the credit is stored
 * on the asset and rendered on the page.
 *
 *     php craft fernbrook/seed/run
 *     php craft fernbrook/seed/run --replace
 */
class SeedController extends Controller
{
    /**
     * @var bool Delete the existing seeded content and rebuild it from scratch.
     */
    public bool $replace = false;

    /** @var array<string, Asset> */
    private array $assets = [];

    /** @var array<string, Category> */
    private array $categories = [];

    private ?User $author = null;

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), ['replace']);
    }

    public function actionRun(): int
    {
        $root = Craft::getAlias('@root') . '/seed';

        if (!is_dir($root)) {
            $this->stderr("No seed directory at $root\n");
            return ExitCode::NOINPUT;
        }

        $content = require "$root/content.php";
        $pages = require "$root/pages.php";

        if ($this->replace) {
            $this->wipe();
        }

        $this->stdout("Seeding Fernbrook Roasters\n\n");

        $this->seedAssets("$root/images");
        $this->seedAuthor($content['author']);
        $this->seedCategories($content['categories']);
        $this->seedServices($content['services']);
        $this->seedJournal($content['journal']);
        $this->linkServiceJournal($content['services']);
        $this->seedLocations($content['locations']);
        $this->seedPages($pages);
        $this->fillPageContent($pages);
        $this->seedGlobals($content['globals']);

        Craft::$app->getElements()->invalidateAllCaches();

        $this->stdout("\nDone.\n");

        return ExitCode::OK;
    }

    // -----------------------------------------------------------------

    private function wipe(): void
    {
        $this->stdout("Removing existing seeded content\n");

        $elements = Craft::$app->getElements();

        foreach (['pages', 'journal', 'services', 'locations'] as $section) {
            $entries = Entry::find()->section($section)->status(null)->all();
            foreach ($entries as $entry) {
                $elements->deleteElement($entry, true);
            }
            $this->stdout("  cleared $section (" . count($entries) . ")\n");
        }

        foreach (Category::find()->group('journalTopics')->status(null)->all() as $category) {
            $elements->deleteElement($category, true);
        }

        foreach (Asset::find()->volume('images')->all() as $asset) {
            $elements->deleteElement($asset, true);
        }

        $this->stdout("\n");
    }

    private function seedAssets(string $dir): void
    {
        $this->stdout("Assets\n");

        $volume = Craft::$app->getVolumes()->getVolumeByHandle('images');
        $folder = Craft::$app->getAssets()->getRootFolderByVolumeId($volume->id);
        $credits = json_decode(file_get_contents("$dir/credits.json"), true);
        $altText = json_decode(file_get_contents("$dir/alt.json"), true);

        foreach (glob("$dir/*.jpg") as $path) {
            $filename = basename($path);

            $existing = Asset::find()
                ->volume('images')
                ->filename($filename)
                ->one();

            if ($existing) {
                // Keep alt text and credits in step with the seed data even
                // when the file itself is already uploaded.
                $existing->alt = $altText[$filename] ?? $existing->alt;
                if (isset($credits[$filename])) {
                    $existing->setFieldValue('assetCredit', $this->creditLine($credits[$filename]));
                }
                Craft::$app->getElements()->saveElement($existing);
                $this->assets[$filename] = $existing;
                continue;
            }

            $credit = $credits[$filename] ?? null;

            // Craft moves the file it is given, so work on a copy.
            $temp = Craft::$app->getPath()->getTempPath() . '/' . $filename;
            FileHelper::createDirectory(dirname($temp));
            copy($path, $temp);

            $asset = new Asset();
            $asset->tempFilePath = $temp;
            $asset->setFilename($filename);
            $asset->newFolderId = $folder->id;
            $asset->setVolumeId($volume->id);
            $asset->avoidFilenameConflicts = true;
            $asset->setScenario(Asset::SCENARIO_CREATE);
            $asset->title = AssetsHelper::filename2Title(pathinfo($filename, PATHINFO_FILENAME));
            $asset->alt = $altText[$filename] ?? $asset->title;

            if ($credit) {
                $asset->setFieldValue('assetCredit', $this->creditLine($credit));
            }

            if (!Craft::$app->getElements()->saveElement($asset)) {
                throw new RuntimeException("Asset $filename: " . $this->errors($asset));
            }

            $this->assets[$filename] = $asset;
            $this->stdout("  $filename\n");
        }
    }

    /**
     * @param array{title: string, artist: string, licence: string} $credit
     */
    private function creditLine(array $credit): string
    {
        $title = preg_replace('/\.(jpe?g|png|tiff?|gif|webp)$/i', '', $credit['title']);

        return sprintf('%s by %s, %s', $title, $credit['artist'], $credit['licence']);
    }

    private function seedAuthor(array $spec): void
    {
        $this->stdout("Author\n");

        // Craft Solo allows a single user account, so the admin is also the
        // byline. The profile fields are what the Journal templates render.
        $user = User::find()->admin()->one() ?? Craft::$app->getUser()->getIdentity();

        if (!$user) {
            throw new RuntimeException('No admin user found to use as the author.');
        }

        $user->fullName = $spec['fullName'];
        $user->setFieldValue('userRole', $spec['role']);
        $user->setFieldValue('userBio', $spec['bio']);

        if (!Craft::$app->getElements()->saveElement($user)) {
            throw new RuntimeException('Author: ' . $this->errors($user));
        }

        $photo = $this->assets[$spec['photo']] ?? null;
        if ($photo && $user->photoId !== $photo->id) {
            $user->photoId = $photo->id;
            Craft::$app->getElements()->saveElement($user);
        }

        $this->author = $user;
        $this->stdout("  {$user->fullName}\n");
    }

    private function seedCategories(array $specs): void
    {
        $this->stdout("Categories\n");

        $group = Craft::$app->getCategories()->getGroupByHandle('journalTopics');

        foreach ($specs as $spec) {
            $category = Category::find()->group('journalTopics')->slug($spec['slug'])->status(null)->one()
                ?? new Category(['groupId' => $group->id]);

            $category->title = $spec['title'];
            $category->slug = $spec['slug'];
            $category->setFieldValue('summary', $spec['summary']);

            if (!Craft::$app->getElements()->saveElement($category)) {
                throw new RuntimeException("Category {$spec['slug']}: " . $this->errors($category));
            }

            $this->categories[$spec['slug']] = $category;
            $this->stdout("  {$spec['title']}\n");
        }
    }

    private function seedServices(array $specs, ?Entry $parent = null): void
    {
        if ($parent === null) {
            $this->stdout("Services\n");
        }

        foreach ($specs as $spec) {
            $entry = $this->upsertEntry('services', 'service', $spec['slug'], function(Entry $entry) use ($spec, $parent) {
                $entry->title = $spec['title'];
                $entry->setFieldValue('summary', $spec['summary']);
                $entry->setFieldValue('bodyText', $spec['body']);
                $entry->setFieldValue('serviceIcon', $spec['icon']);
                $entry->setFieldValue('priceFrom', $spec['priceFrom']);
                $entry->setFieldValue('seoDescription', $spec['summary']);

                if (!empty($spec['image']) && isset($this->assets[$spec['image']])) {
                    $entry->setFieldValue('heroImage', [$this->assets[$spec['image']]->id]);
                }
            }, $parent);

            $this->stdout('  ' . ($parent ? '- ' : '') . "{$spec['title']}\n");

            if (!empty($spec['children'])) {
                $this->seedServices($spec['children'], $entry);
            }
        }
    }

    private function seedJournal(array $specs): void
    {
        $this->stdout("Journal\n");

        foreach ($specs as $spec) {
            $this->upsertEntry('journal', 'journalEntry', $spec['slug'], function(Entry $entry) use ($spec) {
                $entry->title = $spec['title'];
                $entry->postDate = new DateTime($spec['date']);
                $entry->setFieldValue('standfirst', $spec['standfirst']);
                $entry->setFieldValue('bodyText', $spec['body']);
                $entry->setFieldValue('seoDescription', $spec['standfirst']);
                $entry->setFieldValue('articleAuthor', [$this->author->id]);
                $entry->setFieldValue('heroImage', [$this->assets[$spec['image']]->id]);
                $entry->setFieldValue('journalTopics', array_map(
                    fn(string $slug) => $this->categories[$slug]->id,
                    $spec['topics'],
                ));
            });

            $this->stdout("  {$spec['title']}\n");
        }
    }

    /**
     * Services reference Journal entries, which do not exist when the services
     * are first written, so the relation is filled in on a second pass.
     */
    private function linkServiceJournal(array $specs): void
    {
        foreach ($specs as $spec) {
            if (!empty($spec['journal'])) {
                $entry = Entry::find()->section('services')->slug($spec['slug'])->status(null)->one();
                $ids = Entry::find()->section('journal')->slug($spec['journal'])->status(null)->ids();
                $entry->setFieldValue('relatedJournal', $ids);

                if (!Craft::$app->getElements()->saveElement($entry)) {
                    throw new RuntimeException("Service {$spec['slug']} relations: " . $this->errors($entry));
                }
            }

            if (!empty($spec['children'])) {
                $this->linkServiceJournal($spec['children']);
            }
        }
    }

    private function seedLocations(array $specs): void
    {
        $this->stdout("Locations\n");

        foreach ($specs as $spec) {
            $this->upsertEntry('locations', 'location', $spec['slug'], function(Entry $entry) use ($spec) {
                $entry->title = $spec['title'];
                $entry->setFieldValue('summary', $spec['summary']);
                $entry->setFieldValue('bodyText', $spec['body']);
                $entry->setFieldValue('seoDescription', $spec['summary']);
                $entry->setFieldValue('heroImage', [$this->assets[$spec['image']]->id]);
                $entry->setFieldValue('addressLine1', $spec['addressLine1']);
                $entry->setFieldValue('addressLine2', $spec['addressLine2']);
                $entry->setFieldValue('addressCity', $spec['addressCity']);
                $entry->setFieldValue('addressPostcode', $spec['addressPostcode']);
                $entry->setFieldValue('mapUrl', $spec['mapUrl']);
                $entry->setFieldValue('openingHours', $spec['openingHours']);
                $entry->setFieldValue('servicesOffered', Entry::find()
                    ->section('services')
                    ->slug($spec['services'])
                    ->status(null)
                    ->ids());
            });

            $this->stdout("  {$spec['title']}\n");
        }
    }

    /**
     * Pages are written in two passes. The first creates every page so that
     * the URIs exist, the second fills in the page builder, whose call to
     * action blocks link to pages that may come later in the list.
     */
    private function seedPages(array $specs, ?Entry $parent = null): void
    {
        if ($parent === null) {
            $this->stdout("Pages\n");
        }

        foreach ($specs as $spec) {
            $entry = $this->upsertEntry('pages', 'page', $spec['slug'], function(Entry $entry) use ($spec) {
                $entry->title = $spec['title'];
                $entry->setFieldValue('summary', $spec['summary']);
                $entry->setFieldValue('hideFromNav', $spec['hideFromNav'] ?? false);
                $entry->setFieldValue('seoTitle', $spec['seoTitle'] ?? '');
                $entry->setFieldValue('seoDescription', $spec['seoDescription'] ?? $spec['summary']);

                if (!empty($spec['hero']) && isset($this->assets[$spec['hero']])) {
                    $entry->setFieldValue('heroImage', [$this->assets[$spec['hero']]->id]);
                }
            }, $parent);

            $this->stdout('  ' . ($parent ? '- ' : '') . "{$spec['title']}\n");

            if (!empty($spec['children'])) {
                $this->seedPages($spec['children'], $entry);
            }
        }
    }

    private function fillPageContent(array $specs): void
    {
        foreach ($specs as $spec) {
            $entry = Entry::find()->section('pages')->slug($spec['slug'])->status(null)->one();
            $entry->setFieldValue('pageBuilder', $this->matrixValue($spec['blocks']));

            if (!Craft::$app->getElements()->saveElement($entry)) {
                throw new RuntimeException("Page {$spec['slug']} content: " . $this->errors($entry));
            }

            if (!empty($spec['children'])) {
                $this->fillPageContent($spec['children']);
            }
        }
    }

    private function seedGlobals(array $spec): void
    {
        $this->stdout("Globals\n");

        $set = Craft::$app->getGlobals()->getSetByHandle('siteSettings');

        foreach ($spec as $handle => $value) {
            if ($handle === 'announcementLink') {
                $value = $this->linkValue($value);
            }
            $set->setFieldValue($handle, $value);
        }

        if (!Craft::$app->getElements()->saveElement($set)) {
            throw new RuntimeException('Globals: ' . $this->errors($set));
        }

        $this->stdout("  site settings\n");
    }

    // -----------------------------------------------------------------

    /**
     * Turns a flat list of block definitions into the shape Craft's Matrix
     * field expects, resolving any image filenames to asset IDs on the way.
     */
    private function matrixValue(array $blocks): array
    {
        $entries = [];
        $sortOrder = [];

        foreach ($blocks as $i => $block) {
            $key = 'new' . ($i + 1);
            $fields = $block['fields'];

            if (isset($fields['blockImage'])) {
                $fields['blockImage'] = [$this->assets[$fields['blockImage']]->id];
            }

            if (isset($fields['buttonLink'])) {
                $fields['buttonLink'] = $this->linkValue($fields['buttonLink']);
            }

            $entries[$key] = [
                'type' => $block['type'],
                'enabled' => true,
                'fields' => $fields,
            ];
            $sortOrder[] = $key;
        }

        return ['entries' => $entries, 'sortOrder' => $sortOrder];
    }

    /**
     * Turns ['entry' => 'some/uri'] or ['url' => '/path'] into the shape a
     * Link field stores. Entry links are preferred because they survive the
     * target being renamed; listing pages have no entry, so those stay URLs.
     *
     * @param array{entry?: string, url?: string} $spec
     */
    private function linkValue(array $spec): array
    {
        if (isset($spec['entry'])) {
            $target = Entry::find()->uri($spec['entry'])->status(null)->one();

            if (!$target) {
                throw new RuntimeException("No entry found at URI {$spec['entry']} to link to.");
            }

            return ['type' => 'entry', 'value' => (string)$target->id];
        }

        return ['type' => 'url', 'value' => $spec['url']];
    }

    private function upsertEntry(string $section, string $typeHandle, string $slug, callable $populate, ?Entry $parent = null): Entry
    {
        $sectionModel = Craft::$app->getEntries()->getSectionByHandle($section);
        $type = Craft::$app->getEntries()->getEntryTypeByHandle($typeHandle);

        $entry = Entry::find()->section($section)->slug($slug)->status(null)->one();

        if (!$entry) {
            $entry = new Entry();
            $entry->sectionId = $sectionModel->id;
            $entry->typeId = $type->id;
            $entry->slug = $slug;
        }

        $entry->enabled = true;
        $entry->setAuthorIds([$this->author?->id ?? Craft::$app->getUser()->getId()]);

        $populate($entry);

        if ($parent) {
            $entry->setParentId($parent->id);
        }

        try {
            if (!Craft::$app->getElements()->saveElement($entry)) {
                throw new RuntimeException("Entry $slug: " . $this->errors($entry));
            }
        } catch (InvalidElementException $e) {
            throw new RuntimeException("Entry $slug: " . $e->getMessage(), 0, $e);
        }

        return $entry;
    }

    private function errors(object $model): string
    {
        /** @phpstan-ignore-next-line */
        return json_encode($model->getErrors(), JSON_PRETTY_PRINT);
    }
}
