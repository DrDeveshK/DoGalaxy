#!/usr/bin/env python3
"""Upload one small mu-plugin per Do* WP site: strip *-app overlays, serve original-family home."""
from __future__ import annotations

import importlib.util
import sys
import tempfile
import time
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / "bin"))
spec = importlib.util.spec_from_file_location("cpanel", ROOT / "bin" / "cpanel.py")
cp = importlib.util.module_from_spec(spec)
spec.loader.exec_module(cp)

spec2 = importlib.util.spec_from_file_location("build_do_family", ROOT / "bin" / "build-do-family.py")
fam = importlib.util.module_from_spec(spec2)
spec2.loader.exec_module(fam)

CSS = (ROOT / "design" / "udyog.css").read_text()
# drop WP theme header comment for inline <style>
if "*/" in CSS:
    CSS = CSS.split("*/", 1)[1].strip()

SITES = {
    "dorojgar": fam.PRODUCTS["dorojgar"],
    "doswagat": fam.PRODUCTS["doswagat"],
    "dorishta": fam.PRODUCTS["dorishta"],
    "doaaram": fam.PRODUCTS["doaaram"],
}


def html(p: dict) -> str:
    nav = "".join(f'<a href="{h}">{l}</a>' for l, h in p["nav"])
    feats = "".join(
        f'<div class="feature"><div class="icon">{ic}</div><h3>{t}</h3><p>{d}</p></div>'
        for ic, t, d in p["features"]
    )
    return f"""<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>{p['name']}</title><style>{CSS}</style></head><body>
<div class="topbar"><div class="container"><span>{p['name']} — {p['footer']}</span><span>Kusumit Universe · MyDoApp</span></div></div>
<header class="site-header"><div class="container header-inner">
<a class="brand" href="/"><span class="brand-mark">{p['mark'][0]}<span>o</span></span><span>{p['name']}</span></a>
<nav class="nav">{nav}</nav>
<a class="btn" href="{p['cta'][1]}">{p['cta'][0]}</a>
</div></header>
<section class="hero"><div class="container hero-grid"><div>
<span class="eyebrow">{p['eyebrow']}</span>
<h1>{p['h1']}</h1><p>{p['lede']}</p>
<div class="hero-actions"><a class="btn" href="{p['cta'][1]}">{p['cta'][0]}</a>
<a class="btn light" href="{p['cta2'][1]}">{p['cta2'][0]}</a></div>
</div>
<div class="search-panel"><h3>{p['search'][0]}</h3>
<form action="{p['search'][1]}" method="get"><input class="input" name="q" placeholder="{p['search'][2]}"><br><br>
<button class="btn" type="submit">Search</button></form></div></div></section>
<section class="section"><div class="container">
<div class="section-title"><div><h2>What {p['name']} does</h2><p>{p['lede']}</p></div></div>
<div class="grid-3">{feats}</div></div></section>
<footer class="footer"><div class="container footer-grid">
<div><h3>{p['name']}</h3><p>{p['footer']}</p></div>
<div><h4>Do Galaxy</h4>
<a href="https://mydoapp.com">MyDoApp</a><a href="https://doudyog.com">DoUdyog</a>
<a href="https://dorojgar.com">DoRojgar</a><a href="https://dovishram.com">DoVishram</a>
<a href="https://doswagat.com">DoSwagat</a><a href="https://dorishta.com">DoRishta</a>
<a href="https://dobajar.com">DoBajar</a></div>
<div><h4>Company</h4><a href="/join">Join</a><a href="https://kusumit.com">Kusumit</a></div>
</div>
<div class="container" style="border-top:1px solid rgba(255,255,255,.12);margin-top:28px;padding-top:18px;color:#91a8c8">© 2026 {p['name']}. A Kusumit Universe initiative.</div>
</footer></body></html>"""


def mu_source(p: dict) -> str:
    page = html(p).replace("\\", "\\\\").replace("'", "\\'")
    return f"""<?php
add_action('plugins_loaded', function () {{
    if (!function_exists('deactivate_plugins')) {{
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }}
    foreach (glob(WP_PLUGIN_DIR . '/*-app') ?: [] as $dir) {{
        $base = basename($dir);
        $file = $base . '/' . $base . '.php';
        if (function_exists('is_plugin_active') && is_plugin_active($file)) {{
            deactivate_plugins($file);
        }}
    }}
}}, 1);
add_action('template_redirect', function () {{
    if (!is_front_page()) {{
        return;
    }}
    header('Content-Type: text/html; charset=utf-8');
    echo '{page}';
    exit;
}}, 0);
"""


def main() -> None:
    env = cp.load_env()
    for slug, p in SITES.items():
        php = mu_source(p)
        dest = f"/home/koloconi/{slug}.com/wp-content/mu-plugins"
        print(f"==== {slug} ({len(php)} bytes)", flush=True)
        ok = False
        for attempt in range(4):
            try:
                cp.api2(env, "Fileman", "mkdir", path=f"/home/koloconi/{slug}.com/wp-content", name="mu-plugins")
                with tempfile.TemporaryDirectory() as td:
                    f = Path(td) / "do-family-home.php"
                    f.write_text(php)
                    name = f"do-family-home-{int(time.time())}.php"
                    st = cp.upload(env, f, dest, name)
                    print(" upload", st.get("status"), "try", attempt + 1, flush=True)
                    if st.get("status"):
                        cp.api2(env, "Fileman", "fileop", op="unlink", sourcefiles=f"{dest}/do-family-home.php")
                        cp.api2(env, "Fileman", "fileop", op="rename", sourcefiles=f"{dest}/{name}", destfiles=f"{dest}/do-family-home.php")
                        print(" renamed", flush=True)
                        ok = True
                        break
            except Exception as e:
                print(" fail", attempt + 1, type(e).__name__, flush=True)
                time.sleep(8)
        if not ok:
            print(" SKIP", slug, flush=True)
    # Bajar standalone
    print("==== dobajar", flush=True)
    st = cp.upload(env, ROOT / "standalone" / "dobajar" / "index.php", "/home/koloconi/dobajar.com", f"index-{int(time.time())}.php")
    print(" bajar", st.get("status"), flush=True)
    if st.get("status"):
        cp.api2(env, "Fileman", "fileop", op="unlink", sourcefiles="/home/koloconi/dobajar.com/index.php")
        newest = [x for x in (cp.uapi(env, "Fileman", "list_files", dir="/home/koloconi/dobajar.com").get("data") or []) if str(x.get("file","")).startswith("index-")]
        if newest:
            cp.api2(env, "Fileman", "fileop", op="rename",
                    sourcefiles=f"/home/koloconi/dobajar.com/{newest[-1]['file']}",
                    destfiles="/home/koloconi/dobajar.com/index.php")
    print("done", flush=True)


if __name__ == "__main__":
    main()
