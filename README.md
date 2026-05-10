# Gas @ Midway Mews

Online storefront, price board, availability checker, and collection assistant for the
Gas @ Midway Mews refill point.

The site is **collection-first** — customers come to the refill point. The website's
job is to help them check today's price, confirm stock, and get directions before
they travel.

## What's in this build

| Phase | What it includes |
|-------|------------------|
| **1. Static site** | `index.html`, `prices.html`, `reserve.html`, `safety.html`, `contact.html`, shared `assets/` |
| **2. Backend** | PHP API in `api/` (prices, reservations, stock, enquiries, settings, auth) + MySQL `database/schema.sql` |
| **3. Admin** | Login + dashboard in `admin/` for managing prices, stock, reservations, enquiries, settings |
| **4. WhatsApp** | Reservation form composes a WhatsApp message and opens `wa.me`. Settings drive every WhatsApp/phone link on the public site. |
| **5. SEO** | Per-page meta tags, JSON-LD on the homepage, `sitemap.xml`, `robots.txt` |

The static front-end works **without a backend** — `assets/js/app.js` falls back to a
hard-coded price list and the reservation form will open WhatsApp directly. Spinning
up the PHP/MySQL backend turns on the dynamic price/stock and the admin dashboard.

## Folder structure

```
midway-mews-gas/
├── index.html          Home (hero, popular prices, location)
├── prices.html         Full price list
├── reserve.html        Reservation form (saves + opens WhatsApp)
├── safety.html         Refill rules
├── contact.html        Map, phones, message form
├── sitemap.xml         For search engines
├── robots.txt
├── .htaccess           Security headers, deny dotfiles
│
├── admin/
│   ├── login.php       Sign-in form
│   ├── logout.php
│   ├── dashboard.php   Stats + recent reservations
│   ├── reservations.php
│   ├── prices.php      Edit prices, mark popular/active, add new
│   ├── stock.php       Mark sizes available / low / out
│   ├── enquiries.php
│   ├── settings.php    Phone numbers, hours, address, Maps URL
│   ├── _layout.php     Shared sidebar (PHP include)
│   └── _layout_end.php
│
├── api/
│   ├── db.php          PDO connection + helpers
│   ├── auth.php        Login / logout / session helpers
│   ├── prices.php      GET/POST/PUT/DELETE prices
│   ├── reservations.php
│   ├── stock.php
│   ├── enquiries.php
│   ├── settings.php
│   └── .htaccess
│
├── assets/
│   ├── css/styles.css  Brand stylesheet (layered on Bootstrap 5)
│   ├── js/app.js       Shared front-end logic
│   └── images/         Drop logo + photo here
│
└── database/
    └── schema.sql      MySQL DDL + seed data
```

## Setup

### Quick preview (no backend)

Just serve the folder. From the repo root:

```bash
php -S localhost:8000
# or
python3 -m http.server 8000
```

Open <http://localhost:8000>. You'll see the static site with the hardcoded price list.

### Full setup (PHP + MySQL)

1. **Database**

   ```bash
   mysql -u root -p < database/schema.sql
   ```

2. **Database credentials.** Either edit the constants at the top of `api/db.php`
   or set environment variables on your host:

   ```
   DB_HOST=localhost
   DB_NAME=midway_gas
   DB_USER=midway
   DB_PASS=your-password
   ```

3. **Reset the admin password.** The seeded hash in `schema.sql` is a
   placeholder. Generate a fresh one and load it in:

   ```bash
   php -r 'echo password_hash("YOUR_NEW_PASSWORD", PASSWORD_BCRYPT), "\n";'
   ```

   Then in MySQL:

   ```sql
   UPDATE users SET password_hash='<paste hash here>' WHERE email='admin@midwaygas.local';
   ```

4. **Serve.** Point Apache (with `mod_php`) or `php -S` at the project root. The
   `.htaccess` adds security headers and blocks dotfiles.

   For local dev:

   ```bash
   php -S localhost:8000 -t .
   ```

5. **Sign in.** Open <http://localhost:8000/admin/login.php>.

## Replacing placeholders

- **Logo / shop photo.** Drop your assets into `assets/images/`. The hero photo
  block on `index.html` currently shows a coloured placeholder — swap the
  `<div class="placeholder-photo">…</div>` for an `<img>` tag pointing to your
  file.
- **Google Maps URL.** Set in **Admin → Settings** (or update the
  `google_maps_url` row in `business_settings`). Every `[data-map]` link on the
  site picks it up automatically.
- **Phone / WhatsApp numbers.** Same thing — change them once in
  Admin → Settings; every page reflects the change.
- **Trading hours.** Same.

## How the front-end picks up settings

On every page, `assets/js/app.js`:
1. Calls `GET /api/settings.php` and merges the values into `window.GAS_CONFIG`.
2. Walks elements with `data-tel`, `data-tel-display`, `data-wa`, `data-map`,
   `data-trading-hours`, `data-business-name`, `data-address` attributes and
   sets their `href` / text content.
3. If `/api/settings.php` is unavailable (no backend), it uses the defaults
   declared at the top of `app.js`.

## Endpoints

| Method | Path | Purpose | Auth |
|--------|------|---------|------|
| GET    | `/api/prices.php` | Active prices + stock | Public |
| POST   | `/api/prices.php` | Create price | Admin |
| PUT    | `/api/prices.php?id=X` | Update price | Admin |
| GET    | `/api/stock.php` | Stock per size | Public |
| PUT    | `/api/stock.php?id=X` | Update stock (id = cylinder_price_id) | Admin |
| POST   | `/api/reservations.php` | Submit reservation | Public |
| GET    | `/api/reservations.php` | List reservations | Admin |
| PUT    | `/api/reservations.php?id=X` | Update status | Admin |
| POST   | `/api/enquiries.php` | Submit message | Public |
| GET    | `/api/enquiries.php` | List enquiries | Admin |
| PUT    | `/api/enquiries.php?id=X` | Update status | Admin |
| GET    | `/api/settings.php` | Public settings | Public |
| PUT    | `/api/settings.php` | Update settings | Admin |
| POST   | `/api/auth.php?action=login` | Sign in | Public |
| POST   | `/api/auth.php?action=logout` | Sign out | Auth |
| GET    | `/api/auth.php?action=me` | Current user | Public |

## Security notes

- All SQL uses prepared statements (`PDO`).
- Passwords are stored with `password_hash()` (bcrypt). Use the admin UI or a
  manual SQL update to change the seeded password before going live.
- Sessions are HTTP-only and `SameSite=Lax`. Switch to `secure` in production
  (HTTPS).
- The contact form has a hidden honeypot field (`name="website"`) to discourage
  basic spambots.
- `.htaccess` blocks dotfiles, SQL dumps, and adds basic security headers.
- Uncomment the HTTPS redirect block in `.htaccess` once your SSL cert is
  installed.

## Roadmap (Phase 4 / 5 extras not yet wired)

- Email notifications when a new reservation lands. Easiest path: drop a
  `mail()` call into `api/reservations.php` after the insert.
- Twilio / Clickatell SMS for the same.
- Image optimisation when real photos arrive (WebP + responsive `srcset`).
- Add Google Business Profile cross-links on the contact page.

---

**Brand check.** Colour palette and tone follow the design doc:
red `#D90416`, dark red `#A80010`, deep blue `#003B73`, navy `#071A2C`,
warning yellow `#FFC107`, with `#F5F7FA` for the page background and
`#1F2933` for body text.
