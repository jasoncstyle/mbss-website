# MBSS Website — Local Installation

## What You Have

| Folder | Purpose |
|--------|---------|
| `docs/` | Strategic blueprint (what to build) |
| `theme/mbss-theme/` | Custom WordPress theme (design only) |
| `plugin/mbss-operations/` | Business logic (bookings, students, admin) |

## Option A: Docker (Recommended)

### Prerequisites
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed and running

### Steps

1. Copy environment file:
   ```powershell
   cd "c:\Users\jason\Documents\Loopy Learn\mbss-website"
   copy .env.example .env
   ```

2. Start WordPress:
   ```powershell
   docker compose up -d
   ```

3. Open http://localhost:8080 and complete the WordPress 5-minute install.

4. In WordPress admin:
   - **Appearance → Themes** → Activate **MBSS Theme**
   - **Plugins** → Activate **MBSS Operations**

5. Activating the theme creates **draft pages** for all sitemap URLs (Learn to Sail, courses, packages, etc.).

6. Set a static homepage: **Settings → Reading → A static page** (create/select Home).

### Useful Commands

```powershell
# Stop
docker compose down

# View logs
docker compose logs -f wordpress

# WP-CLI (after site is installed)
docker compose run --rm wpcli plugin list
docker compose run --rm wpcli theme list
```

## Option B: Local WP / Staging Host

If you use [Local WP](https://localwp.com/), WP Engine, or similar:

1. Create a new WordPress site.
2. Copy or symlink:
   - `theme/mbss-theme` → `wp-content/themes/mbss-theme`
   - `plugin/mbss-operations` → `wp-content/plugins/mbss-operations`
3. Activate theme and plugin in admin.

### Windows Symlink (optional, requires admin PowerShell)

```powershell
cd "C:\path\to\your\wordpress\wp-content\themes"
New-Item -ItemType SymbolicLink -Name "mbss-theme" -Target "c:\Users\jason\Documents\Loopy Learn\mbss-website\theme\mbss-theme"

cd "C:\path\to\your\wordpress\wp-content\plugins"
New-Item -ItemType SymbolicLink -Name "mbss-operations" -Target "c:\Users\jason\Documents\Loopy Learn\mbss-website\plugin\mbss-operations"
```

## After Activation

### Theme creates
- Draft pages matching [docs/reference/sitemap.md](docs/reference/sitemap.md)
- Block patterns: Hero, Choose Your Path, Sailing Pathway, Why MBSS, Final CTA
- Page templates: `course`, `liveaboard-package`, `booking`

### Plugin creates
- Database tables: `wp_mbss_students`, `wp_mbss_courses`, `wp_mbss_course_sessions`, `wp_mbss_bookings`, `wp_mbss_payments`, `wp_mbss_staff`, `wp_mbss_staff_availability`, `wp_mbss_waivers`, `wp_mbss_audit_log`
- Admin menu: **MBSS** with Dashboard, Course Operations, Students, Financials, Reports
- Shortcode: `[mbss_booking_flow]` on `/book` page
- REST API: `/wp-json/mbss/v1/sessions/upcoming`, `/wp-json/mbss/v1/course-finder`

## Next Build Steps

1. Populate homepage with all 12 sections per [docs/04-homepage.md](docs/04-homepage.md)
2. Build ASA 101 course page per [docs/05-course-pages.md](docs/05-course-pages.md)
3. Implement booking flow steps in `plugin/mbss-operations/modules/bookings/`
4. Seed course catalog data (ASA 101, 103, 104, packages)
5. Connect Stripe for deposits

## Troubleshooting

| Issue | Fix |
|-------|-----|
| Theme not visible | Confirm folder is `wp-content/themes/mbss-theme` with `style.css` inside |
| Plugin won't activate | Requires PHP 8.1+ |
| Pages not created | Switch to another theme, then back to MBSS Theme |
| Booking page empty | Ensure MBSS Operations is active; `/book` uses `[mbss_booking_flow]` |
