# Fernbrook Roasters

A working Craft CMS 5 site, built from an empty directory, deployed to a public URL.

**Fernbrook Roasters does not exist.** It is an invented independent coffee roaster, used so
that the content model has something realistic to model. The name, the two addresses, the
telephone number (Ofcom's reserved 01632 960xxx drama range) and the people are all made up.
Nothing here is client work and nothing is presented as such.

- Live site: https://web-production-924c1.up.railway.app
- Lighthouse on the live home page, mobile preset: performance 98, accessibility 100,
  best practices 100, SEO 100
- Control panel: `/admin`
- Maintenance guide for a non-technical owner: [MAINTENANCE.md](MAINTENANCE.md)

---

## What it is built with

| | |
|---|---|
| CMS | Craft CMS 5 (Solo edition, free) |
| Runtime | PHP 8.3, nginx and PHP-FPM under supervisord |
| Database | MySQL 8 |
| Local dev | DDEV |
| Front end | Twig and one hand-written stylesheet. No build step, no framework. |
| Plugins | CKEditor, Contact Form (both first-party), Redirects |
| Hosting | Railway, from the `Dockerfile` in this repo |

The front end is deliberately plain. The work worth looking at is the content model, the
field layouts and the deployment, not the design.

---

## Running it locally

Requires [DDEV](https://ddev.readthedocs.io/) and a Docker provider (Docker Desktop, OrbStack
or Colima).

```bash
git clone <this repo> fernbrook-roasters
cd fernbrook-roasters

cp .env.example.dev .env          # then fill in CRAFT_APP_ID and CRAFT_SECURITY_KEY
ddev start
ddev composer install

ddev craft setup/app-id           # if you left CRAFT_APP_ID empty
ddev craft setup/security-key     # likewise

ddev craft install \
  --interactive=0 \
  --username=admin \
  --email=you@example.com \
  --password='change-this' \
  --site-name='Fernbrook Roasters' \
  --site-url='$PRIMARY_SITE_URL' \
  --language=en-GB

ddev craft up                     # applies config/project/ to the new database
ddev craft fernbrook/seed/run     # loads the demo content and images

ddev launch
```

Outgoing mail goes to DDEV's Mailpit, at `ddev launch -m`.

### The two console commands

Both live in `modules/fernbrook/` and are safe to run repeatedly.

| Command | What it does |
|---|---|
| `ddev craft fernbrook/schema/build` | Builds the content model in code: filesystems, volumes, image transforms, every field, the Matrix block types, the four sections, the category group, the global set, the user field layout and the mail transport. Craft writes the result to `config/project/`, which is what actually ships. |
| `ddev craft fernbrook/seed/run` | Uploads `seed/images/`, then creates the categories, author profile, services, journal, locations, pages and globals from `seed/content.php` and `seed/pages.php`. `--replace` deletes the existing content first. |

Building the model in a console command rather than clicking it together means the whole
thing can be rebuilt from an empty database, reviewed in a pull request, and re-run after a
merge conflict in the project config.

One thing worth knowing if you read that command: Craft 5 keys an entry's stored content by
the UUID of the **field layout element**, not of the field. Rebuild a layout with freshly
generated element UUIDs and every value already entered against the old ones is orphaned.
The content stays in the database and the page renders blank. So the command derives each
element's UUID from the owner and the field handle, and the same goes for tabs, condition
rules and the native Title and user fields that Craft injects when a layout does not declare
them. That is what makes the command safe to run against a site that already has content.

---

## Environment variables

Copy `.env.example.dev` locally, `.env.example.production` for a server. Nothing secret is
committed.

| Variable | Notes |
|---|---|
| `CRAFT_APP_ID` | `ddev craft setup/app-id` generates one |
| `CRAFT_SECURITY_KEY` | `ddev craft setup/security-key`. Must be the same everywhere a given database is used, or every encrypted value breaks |
| `CRAFT_ENVIRONMENT` | `dev` or `production`. Drives devMode, template caching and whether the control panel can change the schema |
| `CRAFT_DB_DRIVER` `CRAFT_DB_SERVER` `CRAFT_DB_PORT` `CRAFT_DB_DATABASE` `CRAFT_DB_USER` `CRAFT_DB_PASSWORD` | On Railway these come from the MySQL service's own variables |
| `PRIMARY_SITE_URL` | The site's base URL. Also used for the `@web` alias |
| `MAIL_HOST` `MAIL_PORT` `MAIL_USERNAME` `MAIL_PASSWORD` `MAIL_USE_AUTH` | SMTP. Mailpit locally, a transactional provider in production |
| `MAIL_FROM_EMAIL` `MAIL_FROM_NAME` | The envelope sender |
| `CONTACT_FORM_TO_EMAIL` | Where contact form submissions land |
| `CRAFT_ADMIN_USERNAME` `CRAFT_ADMIN_EMAIL` `CRAFT_ADMIN_PASSWORD` | Used once, by the container entrypoint, to install Craft on an empty production database |
| `CRAFT_SEED_ON_INSTALL` | `1` by default. Set to `0` to install Craft without the demo content |
| `PERSIST_DIR` | Where the Railway volume is mounted. Defaults to `/mnt/persist` |

---

## Deploying

The image is built from `Dockerfile`: a Composer stage, then `php:8.3-fpm-alpine` with nginx
and PHP-FPM supervised together and the queue running as a third process. Extensions:
`pdo_mysql`, `gd`, `intl`, `zip`, `bcmath`, `exif`, `pcntl`, `opcache`.

On Railway:

1. Create a project and add a **MySQL** service.
2. Add a service from this repo. Railway picks up `railway.toml` and builds the Dockerfile.
3. Wire `CRAFT_DB_*` to the MySQL service's variables, and set the rest of the variables above.
4. Attach a **volume** to the app service, mounted at `/mnt/persist`.
5. Deploy.

### About that volume

Railway allows one volume per service, and Craft needs two directories to survive a deploy:

- `storage/` — compiled templates, logs, runtime caches, the licence key
- `web/uploads` — everything uploaded through the control panel

So a single volume is mounted at `/mnt/persist` and the container entrypoint symlinks both
directories into it on boot. Without this, every deploy silently deletes the client's
uploaded images. The entrypoint also chowns the mount, because Railway mounts volumes as
root.

### What runs on each deploy

`php craft up` runs from the container entrypoint, not a pre-deploy hook, because Railway
does not mount volumes during pre-deploy and `craft up` needs `storage/`. On the very first
boot against an empty database the entrypoint runs `php craft install` first, then seeds the
demo content.

### Fallbacks

Craft needs a persistent PHP process and a writable filesystem, so Vercel, Netlify and other
serverless hosts are not an option. If Railway is unsuitable, the same Dockerfile runs on
Craft Cloud, Servd, Fortrabbit, or any small VPS with Docker Compose.

---

## The content model

| Section | Type | Notes |
|---|---|---|
| Pages | Structure, 3 levels | Matrix page builder. The home page is an ordinary entry whose URI resolves to `__home__`, so it gets live preview like any other page |
| Journal | Channel | Hero image, author relation, categories, body. Archive paginated six per page |
| Services | Structure, 2 levels | Price-from (Money field), icon, related journal entries |
| Locations | Channel | Address fields, opening hours as a table, map link, services offered |
| Journal Topics | Category group | |
| Site Settings | Global set | Contact details, social links, announcement bar |

Page builder block types: rich text, image with caption, two columns, call to action, pull
quote, embedded contact form.

**Conditional fields.** On the call to action block, *Button label* and *Button links to* only
appear once *Show a button* is switched on. The announcement bar's text and link work the same
way off its on/off switch. These are field layout conditions, so the rule lives in project
config and applies in the control panel, not in a template.

---

## What here demonstrates which maintenance skill

For quoting and proposals, so none of this needs re-reading from the code.

| Skill | Where it is in this build |
|---|---|
| Craft upgrades deploy safely | `config/project/` is committed and `php craft up` runs from the container entrypoint on every deploy. `CRAFT_ALLOW_ADMIN_CHANGES=false` in production stops the live site and the repo drifting apart |
| Multi-environment configuration | One `config/general.php`, environment differences applied from `CRAFT_ENVIRONMENT`. Secrets in `.env`, never committed |
| Content modelling beyond tutorials | Field layout tabs, conditional field layout elements, a two-level structure section, Matrix block types as entry types, Money and Icon fields, a table field |
| Whole model reproducible from nothing | `fernbrook/schema/build` rebuilds the content model from an empty database; `fernbrook/seed/run` rebuilds the content. Running either again changes nothing: field layout elements, condition rules and native elements all get deterministic UUIDs, so a rebuild produces an empty project config diff and leaves existing content attached |
| Image handling and performance | Named transforms (hero, card, thumb) served as WebP with `srcset` and `sizes`, lazy loading everywhere except the hero, which is eager with real dimensions to hold layout |
| Query performance, measured not assumed | Eager loading kept where the debug toolbar shows it helps (`/journal` 109 → 95 queries) and removed from single-entry templates where Craft 5's automatic eager loading already covers it and a manual `.with()` costs queries. Numbers are in the template comments |
| Template caching that invalidates correctly | `{% cache %}` on the listings and navigation. Craft clears these automatically when an element inside them changes; the duration is only a backstop |
| URL migration | Two permanent redirects in `config/redirects.php`, reviewable in a pull request. The Redirects plugin handles ones the owner adds themselves, and logs the 404s that show which are needed |
| Forms and spam | First-party Contact Form plugin, SMTP through a provider, honeypot handled in `modules/fernbrook/Module.php` on the plugin's `beforeSend` event. A caught submission still sees the thank-you page, so bots learn nothing |
| Persistent storage on an ephemeral host | One Railway volume, both Craft directories symlinked into it by the entrypoint, verified by deploying twice and confirming an upload survives |
| Accessibility | Skip link, visible focus states, real alt text on every image, labelled form fields, a table caption, `aria-current` on the navigation |
| Editor experience | Live preview on Pages and Journal including Matrix blocks, field instructions written for an editor rather than a developer, conditional fields so no one sees a setting that does nothing |
| Handover | [MAINTENANCE.md](MAINTENANCE.md), written for a marketing agency rather than a developer |

---

## Photographs

Every image in `seed/images/` is from Wikimedia Commons under a Creative Commons licence or
in the public domain, except the author avatar, which is generated. Each file's title, author
and licence are in `seed/images/credits.json`, stored on the asset in Craft when it is seeded,
and rendered on the page under the image it belongs to. Alt text is written per image in
`seed/images/alt.json`.

## Two things to watch

**Railway is retiring `railway.toml`.** The platform is moving to an authoring file at
`.railway/railway.ts`, and the warning says the old files keep working until 1 December 2026.
`railway config migrate --variables=unmanaged --apply` generates the replacement, followed by
`railway config migrate cutover` to switch the service over. It is left on the old format
here deliberately, because `railway.toml` is four readable lines and the generated TypeScript
drops the restart policy, but it is a dated piece of config and should move before then.

## A note on the Redirects plugin

`venveo/craft-redirect` is currently on a 5.x beta. It is the practical option for Craft 5 and
it works, but on a client site that is worth knowing before it goes in: a beta dependency is a
thing to check at every upgrade. The two redirects this site actually needs are in
`config/redirects.php`, which is first-party Craft and has no plugin behind it, so the plugin
can be removed without breaking any URL.
