---
name: cpanel
description: Drive HostingRaja cPanel account koloconi via UAPI from Cursor. Use when deploying DoGalaxy themes, listing domains, uploading files, or managing the server27a host.
---

# cPanel (koloconi @ server27a)

Do **not** open `cpsess…` URLs or ask for the cPanel password.

## Auth

Creds live only in `~/.config/dogalaxy/cpanel.env` (`CPANEL_TOKEN`).
If missing, tell the user: cPanel → **Security → Manage API Tokens** → create `cursor-dogalaxy` → paste token into that file. Never paste the token in chat.

## Client

```bash
python3 bin/cpanel.py ping
python3 bin/cpanel.py domains
python3 bin/cpanel.py files /home/koloconi
python3 bin/cpanel.py call DomainInfo list_domains
python3 bin/cpanel.py deploy doswagat
```

`deploy <slug>` uploads `dogalaxy-core` + child theme into that domain's `wp-content/themes`. Theme **activation** is still WP Admin (or a WP application password later).

## Limits

- UAPI/API2 = this cPanel account only (files, domains, email, DBs, SSL, Git).
- No inbound SSH (port 22 refused). No WHM/root.
- Softaculous WP install is still a cPanel UI click unless Softaculous API is enabled.
- Never commit `cpanel.env` or tokens.
