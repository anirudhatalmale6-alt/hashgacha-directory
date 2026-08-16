# Hashgacha — single-page site with admin panel

A one-page website for a Hashgacha organisation: about text, a Request Hashgacha
button pointing at a Google Form, a grid of certified business logos that open a
contact popup, and the Hashgacha's own contact details — all editable from a
password-protected admin panel.

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
| **Page Content** | Headline, About text and its three bullet points, the Request Hashgacha wording, the Google Form link and the button label, and the directory intro. |
| **Contact & Branding** | The Hashgacha logo, name, tagline, accent colour, and every contact detail in the Contact section and footer. |
| **Account** | Your admin username and password. |

Notes:

- Leave any contact field empty and its card simply disappears from the site.
- The **Category** field on a business creates the filter buttons above the grid.
  Leave it blank if you do not want categories.
- WhatsApp numbers need the country code (`+15550102030`) — that is what
  `wa.me` expects.
- A website typed without `https://` gets it added automatically.
- The search box appears once there are more than five businesses. It can be
  switched off in *Page Content*.

## Demo content

`tools/seed_demo.php` fills the site with ten sample businesses and generated
placeholder logos so the layout can be reviewed before the real content exists.

```
php tools/seed_demo.php
```

Re-running it replaces the demo rows and leaves anything you added yourself
untouched. To clear the demo out completely, delete those ten rows from the
admin panel — or delete `data/site.sqlite` to start from a blank site.

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
tools/seed_demo.php  demo content generator
```
