"""Builds the admin guide PDF by driving the real control panel.

    python3 tools/build_guide.py http://127.0.0.1:8485 PASSWORD out.pdf

Every picture in the guide is a screenshot taken from a running copy of the
site, so the guide cannot drift out of step with the panel: rebuild it and the
pictures update themselves.

Point it at a throwaway copy of the site — it adds and deletes an example
business as it goes.
"""
import base64
import os
import sys

from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else "http://127.0.0.1:8485"
PASSWORD = sys.argv[2] if len(sys.argv) > 2 else "hashgacha2026"
OUT = sys.argv[3] if len(sys.argv) > 3 else "build/admin-guide.pdf"

shots = {}


def grab(page, name, selector=None, pad=None):
    """Screenshot the viewport, or one element when a selector is given."""
    if selector:
        el = page.locator(selector).first
        el.scroll_into_view_if_needed()
        page.wait_for_timeout(250)
        data = el.screenshot()
    else:
        data = page.screenshot()
    shots[name] = base64.b64encode(data).decode()
    print("  shot:", name, len(data) // 1024, "K")


with sync_playwright() as p:
    browser = p.chromium.launch()
    page = browser.new_page(viewport={"width": 1180, "height": 760},
                            device_scale_factor=2)

    # ---- signing in -------------------------------------------------------
    page.goto(BASE + "/admin/login.php")
    page.fill("input[name=username]", "admin")
    page.fill("input[name=password]", "your-password")
    grab(page, "login", "form.a-login")
    page.fill("input[name=password]", PASSWORD)
    page.click("button[type=submit]")
    page.wait_for_load_state("networkidle")

    # ---- the business list ------------------------------------------------
    grab(page, "list")
    grab(page, "order", ".a-order-mode")

    # ---- adding a business ------------------------------------------------
    page.goto(BASE + "/admin/business.php")
    page.fill("input[name=name]", "Example Bakery")
    page.fill("input[name=category]", "Bakery")
    page.fill("input[name=phone]", "052-123-4567")
    page.fill("input[name=whatsapp]", "058-765-4321")
    page.fill("input[name=email]", "hello@examplebakery.com")
    page.fill("input[name=website]", "www.examplebakery.com")
    grab(page, "form_name", ".a-field:has(input[name=name])")
    grab(page, "form_contact", ".a-card:has(input[name=phone])")
    grab(page, "form_logo", ".a-card:has(#logoInput)")
    grab(page, "form_active", ".a-card:has(input[name=is_active])")

    # Two different numbers -> the popup lists Phone and WhatsApp separately.
    page.click("button[type=submit]")
    page.wait_for_load_state("networkidle")
    page.goto(BASE + "/index.php", wait_until="networkidle")
    page.click(".grid-item:has-text('Example Bakery') .card")
    page.wait_for_timeout(500)
    grab(page, "popup_two", ".modal-panel")

    # Same number in both boxes -> one combined row.
    page.goto(BASE + "/admin/index.php")
    page.locator("tr", has_text="Example Bakery").first.get_by_role("link", name="Edit").click()
    page.wait_for_load_state("networkidle")
    page.fill("input[name=whatsapp]", "052-123-4567")
    grab(page, "form_same", ".a-grid-2")
    page.click("button[type=submit]")
    page.wait_for_load_state("networkidle")
    page.goto(BASE + "/index.php", wait_until="networkidle")
    page.click(".grid-item:has-text('Example Bakery') .card")
    page.wait_for_timeout(500)
    grab(page, "popup_one", ".modal-panel")

    # ---- the other tabs ---------------------------------------------------
    page.goto(BASE + "/admin/content.php")
    grab(page, "content")
    page.goto(BASE + "/admin/contact.php")
    grab(page, "contact")
    page.goto(BASE + "/admin/account.php")
    grab(page, "account")

    # ---- tidy the example away -------------------------------------------
    page.goto(BASE + "/admin/index.php")
    page.on("dialog", lambda d: d.accept())
    # The confirm dialog means the click resolves before the form posts, so wait
    # for the reload rather than for the network to fall quiet.
    with page.expect_navigation():
        page.locator("tr", has_text="Example Bakery").first.get_by_role("button", name="Delete").click()
    left = page.eval_on_selector_all(".a-table tbody .a-strong-link", "e=>e.map(x=>x.textContent)")
    assert "Example Bakery" not in left, "the example business was left behind"
    print("  example business removed, %d businesses left" % len(left))

    # ---- render the guide -------------------------------------------------
    browser.close()


def img(name, caption=""):
    cap = f'<p class="cap">{caption}</p>' if caption else ""
    return (f'<figure><img src="data:image/png;base64,{shots[name]}" alt="">{cap}</figure>')


GUIDE = f"""
<style>
  @page {{ size: A4; margin: 16mm 14mm; }}
  * {{ box-sizing: border-box; }}
  body {{ font: 11pt/1.55 "Helvetica Neue", Arial, sans-serif; color: #1c1c1e; margin: 0; }}
  h1 {{ font-size: 25pt; margin: 0 0 4px; letter-spacing: -.4px; }}
  h2 {{ font-size: 14pt; margin: 26px 0 8px; padding-top: 12px;
       border-top: 2px solid #b29228; page-break-after: avoid; }}
  h3 {{ font-size: 11.5pt; margin: 16px 0 4px; page-break-after: avoid; }}
  p {{ margin: 0 0 9px; }}
  .lead {{ color: #55555c; font-size: 11.5pt; margin-bottom: 20px; }}
  figure {{ margin: 10px 0 14px; page-break-inside: avoid; }}
  img {{ width: 100%; border: 1px solid #dcdce2; border-radius: 6px; display: block; }}
  .cap {{ font-size: 9pt; color: #6a6a72; margin: 5px 0 0; }}
  ol, ul {{ margin: 0 0 10px; padding-left: 20px; }}
  li {{ margin-bottom: 5px; }}
  .note {{ background: #faf6ea; border-left: 3px solid #b29228;
           padding: 10px 13px; margin: 12px 0; page-break-inside: avoid; }}
  .note strong {{ display: block; margin-bottom: 3px; }}
  code {{ background: #f2f2f5; padding: 1px 5px; border-radius: 4px; font-size: 10pt; }}
  .two {{ display: flex; gap: 12px; page-break-inside: avoid; }}
  .two figure {{ flex: 1; margin-top: 0; }}
  .pb {{ page-break-before: always; }}
</style>

<h1>Ramat Eshkol Kosher</h1>
<p class="lead">How to run the website yourself &mdash; adding businesses,
editing the wording, and changing the contact details.</p>

<h2>1. Signing in</h2>
<p>The control panel lives at your address with <code>/admin/</code> on the end,
for example <code>rekosher.org/admin/</code>. Sign in with the username
<code>admin</code> and the password you were sent.</p>
{img("login")}
<div class="note">
  <strong>Change the password straight away</strong>
  Open the <em>Account</em> tab after your first sign-in and set your own
  password. Until you do, anyone with the password I sent could edit the site.
</div>

<h2>2. The list of businesses</h2>
<p>This is the first screen after signing in. Every certified business is
listed here, and the buttons on the right of each row edit it, hide it or
delete it.</p>
{img("list")}
<div class="note">
  <strong>Hide rather than delete</strong>
  <em>Hide</em> takes a business off the website but keeps everything you typed,
  so you can put it back with one click. <em>Delete</em> is permanent. If a
  business is only pausing, hide it.
</div>

<h3>The order they appear in</h3>
<p>At the top of the list you can choose how the businesses are sorted on the
website.</p>
{img("order")}
<ul>
  <li><strong>A&ndash;Z</strong> keeps them alphabetical automatically. Anything
      new you add drops into the right place on its own. This is how the site
      is set now.</li>
  <li><strong>Manual</strong> lets you drag the rows into any order you like.
      Drag handles appear on the left of each row when you switch to it.</li>
</ul>

<h2 class="pb">3. Adding a business</h2>
<p>Click <strong>Add business</strong> at the top of the list. Only the name is
required &mdash; everything else can be filled in later, and any box you leave
empty simply does not appear on the website.</p>
{img("form_name", "The name is the only box you must fill in.")}
<p>The <strong>Category</strong> box is optional. If you use it, a row of filter
buttons appears above the logos on the website so visitors can narrow the list
down to bakeries, caterers and so on. Leave it blank and no filters appear.</p>

<h3>Phone, WhatsApp, email and website</h3>
{img("form_contact")}
<p>Type the numbers exactly as you would write them &mdash; <code>052-123-4567</code>
is fine. The site works out the international form on its own, so the phone
number dials correctly and WhatsApp opens the right chat, whichever country the
visitor is in.</p>
<div class="note">
  <strong>Numbers from outside Israel</strong>
  If a business uses an overseas number for WhatsApp &mdash; a few of yours use
  American ones &mdash; put a <code>+</code> and the country code in front, like
  <code>+1 484-521-1252</code>. Anything without a <code>+</code> is treated as
  Israeli.
</div>

<h2>4. Two numbers, or one</h2>
<p>This is the bit you asked about. It is automatic &mdash; there is no setting
to switch.</p>

<h3>Different numbers &rarr; two separate rows</h3>
<p>Put one number in <strong>Phone</strong> and a different one in
<strong>WhatsApp</strong>, and the popup lists them separately.</p>
{img("popup_two", "Phone and WhatsApp shown as two rows, because the numbers differ.")}

<h3>The same number &rarr; one combined row</h3>
<p>Type the <em>same</em> number into both boxes and the site works out that it
is one line, and shows it once with both buttons on it.</p>
{img("form_same", "The same number typed into both boxes.")}
{img("popup_one", "Which the website turns into a single row: Call &middot; Text &middot; WhatsApp.")}
<div class="note">
  <strong>If a business only has WhatsApp</strong>
  Leave the Phone box empty and fill in WhatsApp only. The popup then shows just
  the WhatsApp row &mdash; it never invents a phone number.
</div>

<h3>The logo</h3>
{img("form_logo")}
<p>Choose a picture file and you will see it before you save. JPG, PNG, GIF,
WEBP and SVG all work, up to 4 MB. A logo on a plain white or transparent
background looks best on the cards. If a business has no logo, its initials are
shown in a circle instead.</p>

{img("form_active", "Untick this to keep a business in your list but off the website.")}

<h2 class="pb">5. Changing the wording on the page</h2>
<p>The <strong>Page Content</strong> tab holds every piece of text on the site:
the tab names along the top, the About paragraphs, the headings, and the link
behind the Request Hashgacha button.</p>
{img("content")}
<p>To point the Request Hashgacha button at a different form, paste the new
address into the Google Form box and save. Nothing else needs touching.</p>

<h2>6. Contact details and colours</h2>
<p>The <strong>Contact &amp; Branding</strong> tab holds the office details at
the foot of the page &mdash; R' Aryeh Frankel's name and title, the phone
number, the email address &mdash; along with the seal at the top of the page and
the two colours the site uses.</p>
{img("contact")}
<p>The same phone-and-WhatsApp rule applies here: the same number in both boxes
gives you the single <em>Call &middot; Text &middot; WhatsApp</em> card that is
on the site now.</p>

<h2>7. Your password</h2>
{img("account")}
<p>The <strong>Account</strong> tab changes your username and password. You need
your current password to save any change, so keep it somewhere safe.</p>

<h2>8. Backing it up</h2>
<p>Everything you type into the panel is kept in a single file on your hosting,
at <code>_app/data/site.sqlite</code>, and the logos sit in the
<code>uploads</code> folder. Download those two from the File Manager now and
then and you have a complete copy of the site.</p>
<div class="note">
  <strong>One folder to leave alone</strong>
  <code>_app</code> holds the website's code and all of its content. Everything
  else can be moved around; that folder should stay exactly where it is.
</div>
"""

os.makedirs(os.path.dirname(OUT) or ".", exist_ok=True)
with sync_playwright() as p:
    browser = p.chromium.launch()
    page = browser.new_page()
    page.set_content(GUIDE, wait_until="load")
    page.pdf(path=OUT, format="A4", print_background=True)
    browser.close()

print("\nwrote", OUT, os.path.getsize(OUT) // 1024, "KB")
