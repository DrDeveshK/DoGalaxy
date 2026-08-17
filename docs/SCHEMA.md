# Do Galaxy schema

MySQL tables (`dg_*`) — not WordPress CPTs. One identity layer, six product modules.

| Module | Tables |
|---|---|
| Trust | `dg_users` `dg_audit` `dg_enquiries` `dg_journeys` |
| Udyog | `dg_businesses` `dg_compliance` |
| Vishram | `dg_stays` `dg_stay_requests` |
| Rojgar | `dg_jobs` `dg_applications` |
| Swagat | `dg_venues` `dg_event_requests` |
| Rishta | `dg_profiles` (21+) `dg_interests` |
| Bajar | `dg_listings` `dg_order_requests` |

SQL: `schema/dogalaxy.sql`.

Do Udyog app lives at **`/app/`** on doudyog.com and uses that site’s existing MySQL (no new database). WordPress homepage is untouched.
