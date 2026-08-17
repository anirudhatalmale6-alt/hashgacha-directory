# Putting the site on Hostinger

The package `build/ramat-eshkol-kosher-site.zip` is the whole site, ready to
run. There is nothing to install, compile or configure on the server — it is
plain PHP with a small file-based database.

Build it with:

    php tools/build_deploy.php --admin-pass=YOUR-PASSWORD

## What is in the package

    index.php        the page itself
    assets/          stylesheet and script
    uploads/         the seal and all the business logos
    admin/           the control panel
    _app/            the code and the database — blocked from the web
    .htaccess        access rules

Everything sits in one folder, so it drops straight into `public_html`. It does
not need the document root moved, and it does not rely on URL rewriting.

## Uploading it

1. hPanel → **Files** → **File Manager**, and open `public_html`.
2. If anything is already in there, select it all and delete it first — an old
   `index.html` will be served instead of the new site.
3. **Upload** `ramat-eshkol-kosher-site.zip`.
4. Right-click it → **Extract** → extract *here*, in `public_html`.
   You should end up with `public_html/index.php`, not
   `public_html/something/index.php`.
5. Delete the zip once it has extracted.
6. Turn on hidden files in the File Manager (the eye icon) and check that
   `.htaccess` and the `_app` folder both arrived.

## Two settings to check

- **PHP version.** hPanel → Websites → your domain → Advanced → PHP
  Configuration. It needs **PHP 8.0 or newer**; 8.2 is a good choice.
- **SQLite.** On the *PHP extensions* tab of the same page, make sure
  `pdo_sqlite` and `sqlite3` are ticked.

If either is wrong the site says so in plain English on screen rather than
showing an error page, so open it and read what it tells you.

## Trying it before the domain moves

Hostinger gives every site a temporary address (something like
`yourname.hostingersite.com`) under Websites → Dashboard. Open the site there
first and click around. `rekosher.org` keeps pointing at the current site until
its DNS is changed, so nothing is disturbed while you check.

When you are happy, point the domain at Hostinger — hPanel → Domains, or change
the nameservers at the registrar to Hostinger's. Allow a few hours for it to
take effect.

## The control panel

`https://your-site/admin/` — username `admin`. The password is the one set when
the package was built (it was sent privately, never in this file).

Change it under **Account** the first time you sign in.

From there you can edit the About text, the Request Hashgacha link, the contact
details, the colours, and add, edit, reorder or hide businesses.

## Backups

Everything you type into the admin lives in one file: `_app/data/site.sqlite`.
The logos live in `uploads/`. Download those two and you have the whole site.

Do not delete `_app` — it holds the code and all the content.

## Moving to a different host later

Copy the whole `public_html` folder as it stands. There is no database server
to export and nothing tied to Hostinger.
