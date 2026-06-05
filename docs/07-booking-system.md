# Booking System Architecture

## Core Principle

Booking should feel like **"I'm reserving my place"** — not "I'm filling out a form."

---

## Strategic Goals

- Reduce friction and confusion
- Increase deposits and package upgrades
- Reduce admin workload
- Create student records (not just registrations)

---

## Booking Flow (8 Steps)

### Step 1 — Choose Your Path

Large cards:
- Learn to Sail
- Advance Your Skills
- Liveaboard Packages
- Online Certifications

### Step 2 — Choose Course or Package

Filtered by path selection. Examples:
- Learn to Sail → ASA 101, ASA 103
- Liveaboard → 101+103, 101+103+104, 103+104, 102+104, 104+114

### Step 3 — Choose Available Date

Calendar view (not giant list). Show:
- Date range
- Seats available (with scarcity: "1 Seat Left", "2 Seats Left")
- Duration
- Instructor (optional)

### Step 4 — Course Summary

| Field | Example |
|-------|---------|
| Course Name | ASA 101 |
| Dates | May 12–14 |
| Course Price | $695 |
| Deposit Due Today | $250 |
| Remaining Balance | $445 |
| Note | Final balance invoiced separately |

### Step 5 — Student Information

- First Name, Last Name, Email, Phone
- Address
- Emergency Contact
- ASA Number (optional)
- Experience Level
- Special Notes

### Step 6 — Upgrade Opportunities

**Private Upgrade** (liveaboard and applicable courses):
> Traveling as a couple, pair of friends, or family? Make this course private for an additional $500.

Checkbox: Add Private Upgrade (+$500)

**Future upsells:** ASA Membership, course materials, weather course, navigation course

### Step 7 — Waivers

- Digital signature required before checkout
- Store signed copy in student record
- PDF export available

### Step 8 — Deposit Payment

| Type | Deposit |
|------|---------|
| Individual course | $250 |
| Combo / liveaboard package | $500 |

- Stripe collects deposit
- Confirmation page + confirmation email + admin notification

**Future:** Stripe for final balance (currently Square invoices sent manually)

---

## Student Record Creation

Every booking immediately creates/updates a **Student Profile** — not just a booking row.

### Student Profile Fields

| Category | Data |
|----------|------|
| Identity | Name, email, phone, address, emergency contact |
| Sailing | ASA number, experience level, notes |
| Enrollment | Course, date, deposit status, balance due, waiver status |
| History | Booking history, certifications earned, payment history |
| Progression | Future course recommendations |

### Example Lifecycle

```
Jason → ASA 101 → ASA 103 → ASA 104 → ASA 114
```

System shows: profile → course history → certifications → future recommendations.

---

## Payment Tracking (Square Integration Phase)

Manual invoice workflow support:

| Action | Fields |
|--------|--------|
| Mark Invoice Sent | Date sent, Square invoice number, notes |
| Mark Paid | Method: Square / Stripe / Cash / Check / Other |

Track deposit method and final payment method separately for historical reporting.

---

## Staff Scheduling

### Roles
- Instructor (Captain)
- Mate
- Office Admin

### Features
- Per-staff availability calendar: Available · Unavailable · Tentative
- Admin assigns: Course → Instructor → Captain → Mate
- Automatic conflict detection
- Email notifications: assigned, removed, course changed

---

## CRM-Lite Lifecycle Stages

| Stage | Description |
|-------|-------------|
| Lead | Initial contact |
| Inquiry | Engaged, not booked |
| Booked | Deposit paid |
| Completed | Course finished |
| Returning Student | Multiple enrollments |
| Inactive | No recent activity |

---

## Waitlist

When course is full:
- Capture: name, email, phone
- Auto-notify when: seat opens, new course added

---

## Email Notifications (Required)

- Booking confirmation
- Course reminder
- Waiver reminder
- Instructor assignment / removal / schedule change
- Balance reminder
- Abandoned booking recovery (see [10-conversion-automation.md](10-conversion-automation.md))

---

## Build Approach

**Hybrid:** Proven WordPress tools where appropriate + custom MBSS modules for:
- Instructors, captains, mates
- Liveaboard packages
- Deposits + Square invoice workflow
- Future Stripe migration
- Student-centric records

**Not** off-the-shelf salon-style booking (Amelia/Bookly alone).

All business logic lives in **MBSS Operations Plugin** — not the theme. See [11-technical-architecture.md](11-technical-architecture.md).

---

## Database Entities

Separate tables/entities (not one giant table):
- Students
- Courses
- Course Sessions
- Bookings
- Payments
- Staff Assignments
- Availability
- Waivers
- Reports

Student-centric: one profile, many enrollments, lifetime history preserved.
