# HostingRaja server27a — account `koloconi`

Source: cPanel Tools PDF, 14 Aug 2026.

| | |
|---|---|
| Primary domain | **kusumit.com** |
| Home | `/home/koloconi` |
| IP | 103.93.16.51 |
| Disk | 3.9 GB used / unlimited |
| MySQL | 28 MB / 19 databases |
| RAM | **4 GB** |
| CPU | 100 units (CloudLinux LVE) |
| Entry processes | 30 |
| Processes | 100 |
| IOPS | 1,024 |
| I/O | 4 MB/s |
| Addon domains | **14** |
| Subdomains | 17 |
| Alias domains | 1 |
| Email | 21 |
| SSH / Terminal / Git | yes |
| Softaculous WordPress | yes |
| MultiPHP | yes |

Fits: WordPress + PHP apps, one-by-one per domain.  
Does not fit: Docker, Node long-running workers, HERO/Glean.

## Deploy

cPanel → Git Version Control, or File Manager → `public_html/<domain>/wp-content/themes/`.
