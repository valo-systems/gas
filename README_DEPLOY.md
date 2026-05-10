# Gas @ Midway Mews — cPanel Deployment Guide

**Live URL:** https://gas.valosystems.co.za  
**Document root:** `/home/valosyst/public_html/gas.valosystems.co.za`

The cPanel subdomain should stay **Not Redirected**. The site is served directly from
the subdomain document root.

## Deployment method

This project uses cPanel Git Deployment via `.cpanel.yml`, following the same pattern
as the CannaStop demo.

On deployment, cPanel copies only the public app files into:

```text
/home/valosyst/public_html/gas.valosystems.co.za
```

Files deployed:

```text
index.html
prices.html
reserve.html
safety.html
contact.html
favicon.svg
robots.txt
sitemap.xml
.htaccess
assets/
api/
admin/
```

The deploy intentionally does not copy `database/`, README files, source notes, or
unused raw asset folders into the public document root.

## cPanel steps

1. cPanel -> Git Version Control.
2. Create or connect the repository for this project.
3. Set the deployment path to:
   ```text
   /home/valosyst/public_html/gas.valosystems.co.za
   ```
4. Deploy HEAD. cPanel will run `.cpanel.yml`.
5. Open:
   ```text
   https://gas.valosystems.co.za
   ```
6. Run AutoSSL if HTTPS is not active yet:
   cPanel -> SSL/TLS Status -> Run AutoSSL.

## Database setup

The public pages work with fallback price/settings data if MySQL is not ready, but
the admin and API need a database.

1. Create a MySQL database and user in cPanel.
2. Import `database/schema.sql` manually through phpMyAdmin.
3. Update `api/db.php` credentials or configure matching environment variables:
   ```text
   DB_HOST=localhost
   DB_NAME=midway_gas
   DB_USER=your_cpanel_db_user
   DB_PASS=your_cpanel_db_password
   ```
4. Generate a new admin password hash and update the seeded admin user.

## Post-deploy checklist

- [ ] Homepage loads at `https://gas.valosystems.co.za`
- [ ] Favicon loads
- [ ] Prices page renders fallback or database prices
- [ ] Reserve page opens WhatsApp correctly
- [ ] Contact page map and directions work
- [ ] Admin login opens at `/admin/login.php`
- [ ] AutoSSL is active
