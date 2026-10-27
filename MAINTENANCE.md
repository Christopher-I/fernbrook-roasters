# Looking after this Craft CMS site

This is written for the people who own the website, not for developers. It explains what
maintenance a Craft site needs, how often, and what happens when it is skipped.

---

## The short version

A Craft site needs about an hour of attention a month. Skip it for a year and that hour
turns into a day or two of work, because the updates stop being small.

| How often | What happens | Who does it |
|---|---|---|
| Monthly | Updates applied and checked on a copy of the site first | Developer |
| Monthly | Backup restore spot-check | Developer |
| Before any big change | Database backup taken from the admin area | Anyone |
| Yearly | PHP version review | Developer |

---

## The monthly routine

Everything below happens on a copy of the site first, never straight on the live one.

1. **See what is waiting.** Craft lists available updates in its own admin area under
   Utilities, Updates. Each one shows the version you are on and the version available.
2. **Read the notices.** Craft marks updates in three ways. A plain update is routine. One
   flagged **critical** is a security fix and should go out within a day or two, not at the
   end of the month. One marked **breaking** means something on the site may stop working,
   so it needs testing before it ships, and sometimes a bit of development work.
3. **Check the plugins first.** Craft will tell you if a plugin has no compatible version
   yet. If one is holding things back, that is worth knowing before the update starts, not
   halfway through. Occasionally a plugin is abandoned and has to be replaced, which is a
   separate piece of work and gets quoted separately.
4. **Apply the updates** on the copy, then run the site's update command so the database
   catches up with the new code.
5. **Click through the site.** Home page, a Journal article, a Service page, the contact
   form, and the admin area. If something broke, it shows up here.
6. **Ship it**, which on this site means pushing the change to GitHub. The hosting picks it
   up and deploys automatically.

For developers, the commands behind steps 4 and 6 are `composer update`, `php craft up`,
and a `git push`. `php craft up` runs on every deploy automatically.

---

## Content structure changes

This site keeps its entire content structure, every field, section and setting, in files
alongside the code. That means a change made to the structure on a test copy can be
reviewed, approved and deployed exactly like a code change, instead of being re-done by
hand on the live site.

The practical effect for you: the live admin area is locked against structural edits.
Editors can add and change content freely. Changing what fields exist is a developer task
that goes through the normal deploy. This is deliberate. It is what stops a live site and
a test site drifting apart until nobody is sure which is correct.

---

## Backups

Two things need backing up, and they are separate.

- **The database.** All the written content, settings, users and structure.
- **The uploaded files.** Images and documents added through the admin area.

On this site, a backup of the database can be taken at any time from Craft's own admin area,
under Utilities, Database Backup. It downloads a file. Do this before any large content
change, and keep the file somewhere other than the server.

Uploaded files live on a storage volume that is separate from the code, so they survive every
deploy. That is not the same as a backup: it protects against a deploy wiping them, not
against someone deleting them. Scheduled off-server backups of both the database and the
uploads are a hosting setting, and whether they are switched on depends on the plan the site
is hosted under. It is worth confirming in writing which of the two is in place before
assuming either.

**Restoring** means loading the database dump back in and copying the uploaded files back.
The important part is that this gets tested, not just assumed. A backup nobody has ever
restored is not a backup. The monthly routine above is the natural place to test it: restore
the latest backup onto the test copy, which both proves the backup works and gives a fresh,
realistic site to try the updates on.

---

## PHP version upgrades

PHP is the language the site runs on. Each version is supported for about two years, then
stops getting security fixes, and hosts eventually force the move.

This is the one upgrade that genuinely needs planning. The sequence is: check that the
Craft version in use supports the new PHP version, check the same for every plugin, move
the test copy to the new version, click through the whole site, then change the live one.
On this site the PHP version is written into a single configuration file, so the switch
itself is one line. The testing either side of it is the real work.

Budget half a day, once every couple of years, and do it before the host sets a deadline
rather than after.

---

## What actually breaks on a neglected site

In rough order of how often it happens:

1. **Security updates pile up.** Craft is widely used, so vulnerabilities get found and
   patched. A site a year behind has usually missed several. This is the one that matters.
2. **The updates stop being small.** Moving up one version at a time is routine. Jumping
   several at once means breaking changes stack on top of each other and have to be
   untangled together.
3. **A plugin gets left behind.** The plugin author stops updating, the site cannot move to
   the next Craft version until that plugin is replaced, and now there is unplanned work.
4. **The PHP version goes out of support** and the host gives notice, which turns a planned
   upgrade into an urgent one.
5. **Licences lapse.** Paid Craft editions and paid plugins include a year of updates. Once
   that runs out the site keeps working, but you cannot update it until the licence is
   renewed. This site runs on free Craft Solo and free plugins, so it is not affected, but
   it catches people out on larger sites.
6. **Nobody checked the backups.** Usually discovered on the day they are needed.

---

## Who to call and what to say

If something looks wrong, the useful details are: what page, what you did, what you
expected, what happened instead, and roughly when. A screenshot of the whole browser
window, address bar included, saves a lot of back and forth.

If the site is completely down, say so first. That gets a different response than a broken
layout on one page.
