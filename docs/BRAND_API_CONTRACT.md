# Brand API Contract

The dashboard pulls data from each brand's live site over HTTP. Every brand
site (dental, aesthetics, hair, ortho, nextcell) must expose the endpoints
below. All responses are JSON and all requests arrive with
`Authorization: Bearer <BRAND_X_TOKEN>` (the tokens are stored in the
dashboard's `.env`, one per brand).

Base URL (per brand) is whatever `BRAND_<KEY>_URL` is set to. The dashboard
always calls:

```
GET  {base}/api/dashboard/locations
GET  {base}/api/dashboard/stats?from=YYYY-MM-DD&to=YYYY-MM-DD&location=<id>
```

Both endpoints must return HTTP 200 with the envelope `{ "data": ... }`.

---

## 1. `GET /api/dashboard/locations`

Returns the active centres/branches for this brand.

```json
{
  "data": [
    { "id": 1, "name": "DHA Karachi" },
    { "id": 2, "name": "Gulberg Lahore" }
  ]
}
```

---

## 2. `GET /api/dashboard/stats`

Query parameters:

| name     | type       | required | notes                                     |
|----------|------------|----------|-------------------------------------------|
| from     | YYYY-MM-DD | yes      | inclusive start date                      |
| to       | YYYY-MM-DD | yes      | inclusive end date                        |
| location | integer    | no       | centre id — omit to include all centres   |

Response envelope (any missing section is treated as empty):

```json
{
  "data": {
    "totals": {
      "collection":    1234567,
      "consumed":       987654,
      "consultations":     120,
      "treatments":         84,
      "leads":             210,
      "plans":              46
    },

    "collection_by_branch":   [{ "name": "DHA Karachi",    "amount": 450000 }],
    "consumed_by_branch":     [{ "name": "DHA Karachi",    "amount": 310000 }],
    "collection_by_category": [{ "name": "Hair Transplant","amount": 220000 }],
    "consumed_by_category":   [{ "name": "Hair Transplant","amount": 180000 }],
    "collection_by_service":  [{ "name": "FUE 2500 grafts","amount": 160000 }]
  }
}
```

### Suggested implementation on each brand

Each existing akg branch (dental, aesthetics, …) should add:

1. A route in `routes/api.php` protected by `auth:sanctum` (or a simple
   middleware checking a static token against `config/services.dashboard_token`).
2. A `DashboardApiController` that reuses the same queries behind the current
   `admin.home` dashboard, scoped to the date range + optional location.
3. An `.env` entry `DASHBOARD_API_TOKEN=...` whose value matches the
   `BRAND_<KEY>_TOKEN` entry in the dashboard project.

The dashboard treats a failing/empty response as `0`s — a brand that is down
will not break the overall page; it will just contribute zero.
