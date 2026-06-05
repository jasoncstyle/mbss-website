# Technical Architecture & WordPress Build Strategy

## Core Philosophy

Build like a **business platform** — not a marketing website, custom software project, or startup app.

Optimize for: **transferability · ease of ownership · low maintenance · security · scalability**

Target: potential MBSS sale in 2–5 years.

---

## Technical Objectives

- Fast
- Secure
- Mobile-first
- SEO-friendly
- Extensible
- Easy to maintain

---

## WordPress Architecture

### Theme: Custom (Required)

**Do not use:** purchased themes, ThemeForest, Avada, Divi, Elementor-heavy builds.

**Why:** future ownership, performance, maintainability, SEO, cleaner code.

### Page Builder: Gutenberg + Custom Block Library

Allows future owners to edit pages, add content, reorder sections without breaking layouts.

**Avoid Elementor** unless absolutely necessary.

---

## Separation of Concerns (Critical)

| Layer | Responsibility |
|-------|----------------|
| **Custom Theme** | Visual design only |
| **MBSS Operations Plugin** | All business logic |

Business functionality must survive theme redesigns.

### MBSS Operations Plugin Contains

- Student records
- Booking workflow
- Deposits & payment tracking
- Scheduling & staff assignments
- Reporting & CRM-lite
- Waivers
- Student progression engine

**Not** spread across 20 plugins. **Not** embedded in theme. **Not** agency-proprietary lock-in.

---

## Booking Build Approach

**Hybrid:** WordPress tools where appropriate + custom MBSS modules.

**Do not rely solely on** Amelia, Bookly, or generic scheduling tools.

Unique workflow requirements:
- Instructors, captains, mates
- Liveaboard packages
- Deposits + Square invoices
- Future Stripe migration

---

## Hosting

**Recommended:** WP Engine · Rocket.net · Kinsta

**Avoid:** GoDaddy shared, Bluehost, cheap shared plans.

---

## Environments (Contractual Requirement)

| Environment | Purpose |
|-------------|---------|
| Development | Active development |
| Staging | Review & QA |
| Production | Live site |

**No direct edits on production. Ever.**

---

## Database Architecture

Student-centric separate entities:

- Students
- Courses
- Course Sessions
- Bookings
- Payments
- Staff Assignments
- Availability
- Waivers
- Reports

One student profile → lifetime enrollment history.

---

## API Readiness

Platform must expose APIs for future integrations:
- Ocean Sailing Adventures
- Mobile app
- Student portal
- Learning platform
- Accounting software

---

## Security Requirements

| Requirement | Detail |
|-------------|--------|
| SSL | Mandatory |
| Backups | Daily, 30-day retention |
| Firewall | Wordfence |
| Malware monitoring | Active |
| 2FA | Optional admins, required Super Admin |
| Audit logging | All critical admin actions |

Restore capability: entire site, database, individual records.

---

## Performance Requirements (Contractual)

| Metric | Target |
|--------|--------|
| Google PageSpeed | 90+ |
| Largest Contentful Paint | < 2.5s |
| Core Web Vitals | Compliant |

### Image Strategy
- Automatic optimization
- WebP / AVIF when supported
- No giant uploads

---

## Recommended Plugin Stack

Every plugin must justify its existence. Target ~15 great plugins, not 50 average.

| Category | Plugin |
|----------|--------|
| SEO | Rank Math Pro |
| Performance | WP Rocket, Perfmatters |
| Images | Imagify |
| Security | Wordfence |
| Email | FluentSMTP |
| CRM | FluentCRM |
| Forms | Fluent Forms |
| Analytics | GA4, Search Console, Microsoft Clarity |

---

## Documentation Deliverables (At Project Completion)

- Admin Guide
- Staff Guide
- Technical Guide
- Video Walkthroughs

---

## Ownership Requirements

MBSS must own:
- Domain
- Hosting account
- Theme source code
- Plugin source code
- All student, reporting, and booking data

**No proprietary lock-in.**

---

## Strongest Technical Recommendation

Build the operational system as a **single custom MBSS Operations Plugin**.

This dramatically increases maintainability, transferability, business value, and makes future redesigns far easier.
