# Data Sources

The dashboard reads each brand's own MySQL database directly. All six brand
apps run on the same server, so no API layer is needed and **no brand app is
modified in any way**. Every query goes through `Connection::select()` — the
dashboard never writes to a brand database.

## Brands

| Key | Brand | Site | Git branch | Database |
|---|---|---|---|---|
| `american`   | American Aesthetics       | american.nexthealth.com.pk | `main`       | `u274270318_american` |
| `hair`       | Alkhaleej Hair Transplant | hair.nexthealth.com.pk     | `hair`       | `u274270318_hairtransplant` |
| `ortho`      | OrthoStem                 | ortho.nexthealth.com.pk    | `ortho_new`  | `u274270318_orthostem` |
| `smile`      | Smile                     | smile.nexthealth.com.pk    | `dental`     | `u274270318_dental1` |
| `skin`       | Alkhaleej Skin Clinics    | skin.nexthealth.com.pk     | `aesthetics` | `u274270318_aesthetics` |
| `nexthealth` | NextHealth                | crm.nexthealth.com.pk      | `nextcell`   | `u274270318_nextcell` |

Values verified identical across all six databases: `accounts.id = 1`,
`invoice_statuses` slug `paid` = id `3`.

## Credentials

Preferred setup is **one read-only MySQL user** granted `SELECT` on all six
databases:

```
BRAND_DB_HOST=127.0.0.1
BRAND_DB_PORT=3306
BRAND_DB_USERNAME=<read-only user>
BRAND_DB_PASSWORD=<password>
```

Per-brand overrides (`BRAND_HAIR_DB_USER` / `BRAND_HAIR_DB_PASS`, etc.) exist
for the transition period before that user is created. Once the shared
read-only user is in place, delete the overrides from `.env`.

A brand whose credentials are missing or whose database is unreachable is
skipped and named in a banner on the dashboard — it never breaks the page.

## Metric definitions

These mirror the queries behind each brand app's own `admin.home` dashboard,
so the numbers reconcile.

### Total Collection
`package_advances` — cash actually received.

```sql
SUM(CASE WHEN cash_flow = 'in' THEN cash_amount ELSE -cash_amount END)
WHERE account_id = 1
  AND is_adjustment = 0 AND is_tax = 0 AND is_cancel = 0
  AND (cash_flow = 'in' OR (cash_flow = 'out' AND is_refund = 1))
  AND DATE(created_at) BETWEEN :from AND :to
```

Refunds paid out are subtracted; tax, adjustment and cancellation rows are
excluded. Mirrors `dashboardreport::CollectionByRevenueWidgets()`.

### Total Consumed
`invoices.total_price` for paid invoices.

```sql
SUM(total_price) WHERE invoice_status_id = 3 AND DATE(created_at) BETWEEN :from AND :to
```

Mirrors `HomeController::salesByCentre()`.

### Total Consultations / Treatments
`appointments` counted on `scheduled_date`, split by type
(`appointment_type_id` 1 = consultancy, 2 = treatment).

### Total Leads
`leads` joined to `users` on `patient_id`, where `users.user_type_id = 3`
(patient) and `leads.active = 1`, counted on `leads.created_at`.

Leads carry a `city_id` rather than a centre, so a location filter resolves
through that location's city.

### Total Plans
`packages` created in the period (`deleted_at IS NULL`). In this codebase a
"plan" is a `Packages` row — see `app/Reports/Finanaces.php`, which builds its
plan report from `Packages::...`.

## Charts

| Chart | Source |
|---|---|
| Collection by Branch | `package_advances` grouped by `location_id` |
| Consumed by Branch | paid `invoices` grouped by `location_id` |
| Collection by Service Category | `package_advances` → `appointments.service_id` → `services`, rolled up to `parent_id` |
| Consumed by Service Category | `invoice_details` → `services`, rolled up to `parent_id` |
| Top Services by Consumed | `invoice_details` grouped by leaf `services.name` |
| Collection vs Consumed per Brand | the two totals, per brand |

Category rollup uses `COALESCE(parent.name, service.name)`: a service with a
parent reports under the parent, a top-level service reports under itself.

Service-level series are capped at the top 10 rows per brand
(`BrandStatsService::TOP_N`) so one brand with hundreds of services cannot
swamp a chart.

## Excluded locations

Every brand's `locations` table carries aggregate pseudo-rows that are not
real centres. The brand apps filter them out and so do we:

```
All Centres, All Central Region, All South Region
```

Real centres as of deployment:

| Brand | Centres |
|---|---|
| American Aesthetics | Bahadurabad, Gulshan |
| Alkhaleej Hair Transplant | Alkhaleej Clinics, Alkhaleej DHA Karachi |
| OrthoStem | Alkhaleej Orthostem |
| Smile | Smile Bahadurabad Karachi |
| Alkhaleej Skin Clinics | Alkhaleej Bahadurabad, Alkhaleej DHA Karachi |
| NextHealth | Next Health & Aesthetics, Next Health & Aesthetics Gulshan |
