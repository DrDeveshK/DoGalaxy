# Deploy DoGalaxy themes to cPanel

Repo: [DrDeveshK/DoGalaxy](https://github.com/DrDeveshK/DoGalaxy)

## From this machine (preferred)

cPanel → **Security → Manage API Tokens** → create `cursor-dogalaxy` → put the token in `~/.config/dogalaxy/cpanel.env` (see `cpanel.env.example`).

```bash
python3 bin/cpanel.py ping
python3 bin/cpanel.py deploy doswagat
```

## Once on the server (cPanel Terminal — SSH is closed)

```bash
cd ~
git clone https://github.com/DrDeveshK/DoGalaxy.git
```

Or cPanel → **Git Version Control** → Clone URL `https://github.com/DrDeveshK/DoGalaxy.git`.

## Per domain

1. Softaculous **WordPress** on that domain if it is still SitePad or empty.
2. From the clone:

```bash
cd ~/DoGalaxy && git pull
chmod +x bin/deploy.sh
./bin/deploy.sh doswagat
```

3. WP Admin → Appearance → activate the child theme (parent `dogalaxy-core` stays installed).
4. Old theme stays as rollback.

Core first: `mydoapp` `doudyog` `dovishram` `dorojgar` `doswagat` `dorishta` `dobajar` `drdevesh` `agilehub`.

| Slug | Needs Softaculous WP first? |
|---|---|
| doudyog, dovishram, dorojgar, doswagat, dorishta | no (already WP) |
| mydoapp, dobajar | **yes** — add domain in cPanel first |
| drdevesh, agilehub | yes (SitePad today) |

Do **not** run deploy on `kolocon.in` (same docroot as kusumit.com).

## Local check

```bash
./bin/deploy.sh doswagat --dry-run
```
