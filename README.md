# Ramat Eshkol Kosher — single-page site with admin panel

A one-page website for the Ramat Eshkol Kosher hashgacha: the seal at the top,
an About section, a Request Hashgacha button pointing at a Google Form, a grid
of business logos that open a contact popup, and the hashgacha office's own
contact details — all editable from a password-protected admin panel.

No database server, no build step, no Composer. PHP + SQLite, so it runs on any
normal shared hosting (cPanel, Plesk, or a VPS).

## Requirements

- PHP 8.0 or newer
- The `pdo_sqlite` and `gd` extensions (both are on by default nearly everywhere)
- A writable `data/` and `public/uploads/` folder

## Installing

**Preferred** — point the domain's document root at the `public/` folder and
upload the whole project above it. Nothing but `public/` is then reachable from
the web.

**On normal shared hosting** — upload the whole project folder into
`public_html`. The `.htaccess` in the project root serves the site out of
`public/` for you, and `data/`, `src/` and `tools/` each carry their own
`.htaccess` that denies web access.

Then:

1. Make sure `data/` and `public/uploads/` are writable (permission `755`, or
   `775` if the web server runs as a different user).
2. Open the site once in a browser. The database and default content are created
   automatically on that first request.
3. Go to `/admin/` and sign in:
   - username: `admin`
   - password: `hashgacha2026`
4. Open **Account** and change that password straight away.

## Using the admin panel

| Page | What it controls |
| --- | --- |
| **Certified Businesses** | Add, edit, hide or delete businesses. Drag the rows by the `⋮⋮` handle to change the order the logos appear in, then press *Save new order*. |
| **Page Content** | The four menu tabs, the About text, the Request Hashgacha button label and its Google Form link, and the directory heading and intro. |
| **Contact & Branding** | The seal, the hashgacha name, the accent and button colours, the default phone country code, and every contact detail in the Contact section and footer. |
| **Account** | Your admin username and password. |

Notes:

- Leave any contact field empty and its card simply disappears from the site.
- The **Category** field on a business creates the filter buttons above the grid.
  Leave it blank if you do not want categories.
- Phone numbers are shown exactly as typed. A number starting with `0` is
  treated as local and gets the **default country code** (set on *Contact &
  Branding*, `972` for Israel) added to its call and WhatsApp links. A number
  from another country must be written with a `+`, for example
  `+1 484-521-1252` — that one is left exactly as it is.
- A website typed without `https://` gets it added automatically.
- The search box appears once there are more than five businesses. It can be
  switched off in *Page Content*.

## Starting content

`tools/seed_site.php` loads the hashgacha seal, the office contact details and
the three businesses supplied at the start of the project, copying their logos
out of `content/logos/` into `public/uploads/`.

```
php tools/seed_site.php
```

It is safe to re-run: businesses are matched by name, so it updates them rather
than creating duplicates, and it never overwrites wording that has since been
edited in the admin panel. Pass `--force` if you do want it to overwrite the
text settings back to their starting values.

`content/logos/rek-logo-black.png` is the black version of the seal, in case the
site is ever wanted on a dark background — upload it from *Contact & Branding*.

## Custom paths

Create `config.php` in the project root to move the data or uploads folders,
for example when the host keeps writable files outside the web root:

```php
<?php
define('DATA_DIR', '/home/account/hashgacha-data');
define('UPLOAD_DIR', __DIR__ . '/public/uploads');
```

## Backing up

Everything the site knows lives in two places:

- `data/site.sqlite` — all text, settings and business records
- `public/uploads/` — the logo image files

Copy those two and you have a complete backup.

## Layout

```
.htaccess            serves the site from public/ on shared hosting
config.php           optional path overrides
data/                SQLite database (not web-accessible)
public/              the web root
  index.php          the single page
  assets/            stylesheet and script
  uploads/           logo images
  admin/             admin panel
src/                 bootstrap, database schema, helpers
content/logos/       starting logo files used by the seeder
tools/seed_site.php  starting-content loader
```
