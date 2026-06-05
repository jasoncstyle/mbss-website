# Myrtle Beach Sailing School — Website Rebuild

Strategic blueprint and build documentation for [myrtlebeachsailingschool.com](https://myrtlebeachsailingschool.com).

## Core Principles

1. **Learning paths over course numbers** — Navigation answers "What should I do next?" not "Which ASA number should I click?"
2. **Outcomes over certifications** — Sell transformation; ASA numbers are proof, not the product.
3. **Progression over courses** — "You're taking the next step in your sailing journey."
4. **Student records over registrations** — The operational system centers on lifetime student value.
5. **Transferability** — Built for a potential business sale in 2–5 years (clean code, owned assets, low lock-in).

## Documentation Index

| Doc | Topic |
|-----|-------|
| [01-information-architecture.md](docs/01-information-architecture.md) | Sitemap, navigation, URLs, page purposes |
| [02-brand-messaging.md](docs/02-brand-messaging.md) | Positioning, differentiators, copy hierarchy, CTAs |
| [03-brand-design.md](docs/03-brand-design.md) | Visual system, typography, colors, photography |
| [04-homepage.md](docs/04-homepage.md) | Homepage strategy, wireframe, mobile order, course finder |
| [05-course-pages.md](docs/05-course-pages.md) | Universal course page framework, per-course positioning |
| [06-liveaboard-packages.md](docs/06-liveaboard-packages.md) | Package page strategy (14-section sales pages) |
| [07-booking-system.md](docs/07-booking-system.md) | Booking flow, payments, waivers, student records |
| [08-admin-dashboard.md](docs/08-admin-dashboard.md) | Admin dashboards, reporting, business intelligence |
| [09-content-seo.md](docs/09-content-seo.md) | Content pillars, SEO layers, AI search, schema |
| [10-conversion-automation.md](docs/10-conversion-automation.md) | LTV, automations, CRM, revenue optimization |
| [11-technical-architecture.md](docs/11-technical-architecture.md) | WordPress build, plugin architecture, hosting |
| [12-launch-migration.md](docs/12-launch-migration.md) | Migration, QA, soft launch, growth roadmaps |

### Quick Reference

| Doc | Topic |
|-----|-------|
| [reference/sitemap.md](docs/reference/sitemap.md) | Complete URL map |
| [reference/copy-and-ctas.md](docs/reference/copy-and-ctas.md) | Headlines, taglines, CTA rules |
| [reference/page-templates-checklist.md](docs/reference/page-templates-checklist.md) | Section-by-section build checklists |

## Build Phases (Suggested Order)

1. **Foundation** — Information architecture, brand messaging, design system
2. **Money pages** — Homepage, ASA 101/103/104 course pages, liveaboard flagship package
3. **Booking** — MBSS Operations Plugin, booking flow, student records
4. **Supporting pages** — Remaining courses, packages, About, Why MBSS, Resources hub
5. **Admin** — Dashboards, reporting, staff scheduling
6. **Growth** — Content pipeline, conversion automations, CRM sequences
7. **Launch** — Migration, redirects, QA, soft launch, monitoring

## Platform

WordPress with custom theme (Gutenberg blocks) + custom **MBSS Operations Plugin** for all business logic. See [11-technical-architecture.md](docs/11-technical-architecture.md).

## Project Structure (Scaffold)

```
mbss-website/
├── docs/                          ← Strategic blueprint
├── theme/mbss-theme/              ← Custom block theme (design only)
│   ├── theme.json                 ← Brand colors, typography
│   ├── templates/                 ← page, course, liveaboard-package, booking
│   ├── patterns/                  ← Homepage section patterns
│   └── blocks/                    ← Course Finder, Upcoming Courses
├── plugin/mbss-operations/        ← All business logic
│   ├── modules/                   ← students, bookings, payments, etc.
│   ├── admin/                     ← Dashboard UI
│   ├── api/                       ← REST endpoints
│   └── database/                  ← Table schema
├── docker-compose.yml             ← Local WordPress dev environment
└── INSTALL.md                     ← Setup instructions
```

**Local dev:** See [INSTALL.md](INSTALL.md) — run `docker compose up -d`, open http://localhost:8080, activate theme + plugin.

## Success Metric

Launch is successful when **bookings increase without sacrificing rankings** — not merely when the site goes live.
