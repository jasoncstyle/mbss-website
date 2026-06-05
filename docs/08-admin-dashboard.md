# Admin Dashboard & Business Intelligence

## Philosophy

Answer **"How is the business performing today?"** in less than 60 seconds.

Built around three assets: **Students · Courses · Revenue**

---

## Five Primary Dashboard Areas

1. Dashboard Home
2. Course Operations
3. Students
4. Financials
5. Reports & Analytics

Plus: **Owner Dashboard** (simplified view for future owners)

---

## Dashboard Home

Today's snapshot — not cluttered.

| Widget | Content |
|--------|---------|
| Upcoming Courses (30 days) | ASA 101 Jun 15, ASA 103 Jun 22, 101+103 Jul 1 |
| Seats | Total available, filled, occupancy % |
| Revenue This Month | Booked revenue, deposits collected, balances outstanding, paid in full |
| Recent Bookings | Last 10 enrollments |
| Action Items | Outstanding waivers, balances, waitlist, instructor assignment gaps |

---

## Course Operations Dashboard

Operational heart of the business.

**Filters:** date · instructor · course type · status

**Per course row:**
- Course name, dates
- Instructor, captain, mate
- Students enrolled, capacity, occupancy %
- Revenue, balance due
- Status indicator:
  - Confirmed
  - Low Enrollment
  - Needs Attention
  - Full

---

## Student Dashboard

Permanent profile per student (see [07-booking-system.md](07-booking-system.md)).

### Profile Fields
Name, email, phone, address, emergency contact, ASA number, experience level, notes

### Tabs / Sections
- **Course History** — all enrollments chronologically
- **Certifications Earned** — auto-displayed
- **Financial History** — deposits, balances, payments, refunds
- **Future Recommendations** — e.g., completed 101 → recommend 103

### CRM Lifecycle
Lead → Inquiry → Booked → Completed → Returning → Inactive

---

## Financial Dashboard

### Revenue Overview
Today · This Month · This Year · Trailing 12 Months

### Revenue by Category
- Individual courses
- Liveaboard packages
- Private upgrades
- Online certifications

### Revenue by Course
Per course: revenue, enrollments, avg revenue per student, occupancy %

### Outstanding Balances
Dedicated section with color coding:
- Current
- Due Soon
- Overdue

Fields: student, course, balance, invoice sent?, due date, days outstanding

### Payment Tracking
- Deposit method vs final payment method (Stripe / Square / Cash / Check / Other)
- Payment method report by revenue source

---

## Staff Dashboard

### Instructor Utilization
Per instructor: courses assigned, students taught, revenue generated, days scheduled

### Availability View
Monthly calendar: Available · Unavailable · Tentative · Assigned

Quick assignment management with conflict detection.

---

## Liveaboard Package Dashboard

| Metric | Per Package |
|--------|-------------|
| Enrollments | |
| Revenue | |
| Occupancy | |
| Private Upgrade Rate | |

---

## Online Certification Dashboard

Per course (105, 107, 117, 119, 120): revenue, enrollments, completion rates

---

## Marketing Dashboard

### Lead Sources
Google Organic · Direct · Referral · Facebook · Instagram · Ocean Sailing Adventures · Other

### Conversion Funnel
Visitors → Course Views → Booking Started → Deposit Paid

---

## Business Intelligence Reports

| Report | Purpose |
|--------|---------|
| Enrollment Trend | 12-month enrollment |
| Revenue Trend | 12-month revenue |
| Occupancy Report | By course |
| Instructor Utilization | By instructor |
| Student Retention | 101 → 103 → 104 progression rates |
| Upgrade Conversion | Private upgrade selection rate |

---

## Business Value Dashboard

Metrics that matter for a future sale:

| Metric | Example |
|--------|---------|
| Total Student Database | 2,485 students |
| Active Students (24 mo) | |
| Returning Student % | |
| Average Revenue Per Student | |
| Lifetime Student Value | |

---

## Owner Dashboard (Simplified)

For future owners — understand business health in < 1 minute:

- Revenue
- Bookings
- Occupancy
- Balances Due
- Upcoming Courses
- Student Growth

---

## Revenue Dashboard Additions (from Conversion Strategy)

- Student Lifetime Value
- Average Revenue Per Student
- Repeat Enrollment Rate
- Course Progression Rate (101 → 103 → 104)
- Private Upgrade Rate
- Lead-to-Booking Conversion

---

## Security & Audit

### Mandatory
SSL · Daily backups · Firewall · Malware monitoring · Login protection · 2FA (optional for admins, required for Super Admin)

### Audit Logging
Log all critical admin actions:
- Payment modified
- Refund issued
- Course assignment changed
- User deleted
- Course cancelled

---

## Super Admin Dashboard (from Phase 3)

Additional homepage metrics:
- Students enrolled this month
- Waitlist count
- Private upgrade sales
- Online course sales
