# SkyTydes Safaris — website + dashboard

Plain PHP 8.1+ and MySQL. No build step, no Composer. Runs on any cPanel host.

**Live site:** https://skytydessafaris.com · **Dashboard:** `/admin`

---

## 1. Run it on your computer

1. Install **[Laragon](https://laragon.org/download/)** (Windows, recommended) or **XAMPP**.
2. Clone the repo into the web folder:
   - Laragon: `C:\laragon\www\skytydes`
   - XAMPP: `C:\xampp\htdocs\skytydes`
   ```
   git clone https://github.com/Eddnga/skytydesweb.git skytydes
   ```
3. Start Apache + MySQL, then open **phpMyAdmin** (or HeidiSQL in Laragon) and create an empty database called `skytydes` (collation `utf8mb4_unicode_ci`).
4. Visit **http://localhost/skytydes/install/** (Laragon also gives you `http://skytydes.test/install/`):
   - Host `localhost`, database `skytydes`, user `root`, password empty (XAMPP/Laragon default)
   - Untick "Always use HTTPS" on your computer
   - Create your admin login
5. Open **http://localhost/skytydes/** and **/admin**.

`config.php` and `storage/` are created on your computer and are **ignored by Git** — your passwords never go to GitHub.

## 2. Daily workflow

```
git pull                      # get the latest
# …edit files, check them at http://localhost/skytydes …
git add .
git commit -m "Describe what you changed"
git push
```

## 3. Put it online (cPanel)

**First time**
1. cPanel → **MySQL Databases**: create a database + user, add the user with ALL PRIVILEGES.
2. cPanel → **SSL/TLS Status**: make sure skytydessafaris.com has a certificate (AutoSSL).
3. cPanel → **Git™ Version Control** → *Create*:
   - Clone URL: `https://github.com/Eddnga/skytydesweb.git`
     (private repo: use the SSH URL `git@github.com:Eddnga/skytydesweb.git` and add cPanel's SSH key to GitHub → repo → Settings → Deploy keys)
   - Repository path: `/home/YOUR-CPANEL-USER/repositories/skytydesweb`
4. Open the repo in cPanel → **Pull or Deploy** → **Deploy HEAD Commit**. This copies the site into `public_html` using `.cpanel.yml`.
5. Visit **https://skytydessafaris.com/install/**, fill in the database details, tick HTTPS, create your admin.
6. Done — the installer locks itself. (You can also delete `/install` on the server.)

**Every update after that**
1. `git push` from your computer
2. cPanel → Git Version Control → **Update from Remote** → **Deploy HEAD Commit**

Deploys never overwrite `config.php`, `storage/` or images uploaded in the dashboard. Database changes in new versions are applied automatically the first time the site loads.

---

## What's inside

| Area | What it does |
|---|---|
| Public site | Home, tours + itineraries, destinations, special offers, services, gallery, journal, reviews, FAQs, about, contact |
| Bookings | Every Book/Plan button → short form → WhatsApp with the message pre-written. Each tap is logged as a lead |
| Dashboard | Hero slides, tours, destinations, offers, blog, services, FAQs, gallery, hosts, review approval, WhatsApp leads, newsletter list, settings, users, activity log |
| Security | bcrypt passwords, login lockout, 30-min idle sessions, CSRF on every form, prepared SQL, escaped output, re-encoded image uploads, no scripts in /uploads, spam traps + rate limits, CSP/HSTS headers |

**Text formatting in the dashboard:** `## Heading`, `- bullet`, `**bold**`, `*italic*`, `[link](https://…)`. In headings, `*word*` becomes the gold italic accent.

**Brand fonts:** put `Allenoire.woff2` and the `Lufga-*.woff2` files in `assets/fonts/` and uncomment the `@font-face` block at the top of `assets/css/site.css`.

**Forgot password:** another Admin can reset it under Users — keep at least two admin accounts.
