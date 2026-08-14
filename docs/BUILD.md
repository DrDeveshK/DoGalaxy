# Elite rebuild — one product at a time

Themes are not the product. Each vertical is a **plugin + UI**: register, login, dashboard, directory, workflows, enquiries.

Each vertical is a plugin + UI. MyDoApp is the hub.

| App | Live |
|---|---|
| Do Udyog | doudyog.com |
| Do Vishram | dovishram.com |
| Do Rojgar | dorojgar.com |
| Do Swagat | doswagat.com |
| Do Rishta | dorishta.com |
| Do Bajar | in repo (needs WP + DB slot) |
| MyDoApp | mydoapp.kusumit.com (hub); mydoapp.com when addon+WP exist |

## Standard (every product)

- Distinct sector identity (color, type, voice) on a shared core
- Front page: kicker, promise, proof, catalogue, trust, enquiry
- CPT archive + single (not just a homepage)
- Enquiry stored as a private CPT (no plugin)
- Cross-links to the other five Do products
- Hindi kicker where it belongs; English body
- Mobile-first, no page-builder, no extra JS frameworks

## Sequence

| # | Product | Domain | Status |
|---|---|---|---|
| 0 | DoGalaxy Core | — | built |
| 01 | Do Udyog | doudyog.com | built |
| 02 | Do Vishram | dovishram.com | built |
| 03 | Do Rojgar | dorojgar.com | built |
| 04 | Do Swagat | doswagat.com | built |
| 05 | Do Rishta | dorishta.com | built |
| 06 | Do Bajar | dobajar.com | built (needs addon + WP) |
| 07 | MyDoApp | mydoapp.com | built (needs addon + WP) |

Do not run `bin/gen-themes.py` on elite slugs — it overwrites them.
