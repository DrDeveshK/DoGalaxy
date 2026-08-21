# Do Galaxy schema

MySQL tables (`dg_*`) — not WordPress CPTs. One identity layer, six product modules.

| Module | Tables |
|---|---|
| Trust | `dg_users` `dg_audit` `dg_enquiries` `dg_journeys` |
| Udyog | `dg_businesses` `dg_compliance` |
| Vishram | `dg_stays` `dg_stay_requests` `dg_places` `dg_place_likes` |
| Rojgar | `dg_jobs` `dg_applications` |
| Swagat | `dg_venues` `dg_event_requests` `dg_swipes` |
| Rishta | `dg_profiles` (21+) `dg_interests` `dg_swipes` (private skip/interest) |
| Bajar | `dg_listings` `dg_order_requests` |
| Aaram | `dg_services` `dg_service_requests` `dg_swipes` |
| Nirman | `dg_contractors` `dg_project_leads` |
| Vyapaar | `dg_suppliers` `dg_rfqs` |

SQL: `schema/dogalaxy.sql`.

Do Udyog app lives at **`/app/`** on doudyog.com and uses that site’s existing MySQL (no new database). WordPress homepage is untouched.
