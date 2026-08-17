#!/usr/bin/env python3
"""Drive HostingRaja cPanel (koloconi) via UAPI / API2. Creds: ~/.config/dogalaxy/cpanel.env"""
from __future__ import annotations

import argparse
import json
import os
import ssl
import sys
import tempfile
import urllib.error
import urllib.parse
import urllib.request
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
ENV_PATH = Path.home() / ".config" / "dogalaxy" / "cpanel.env"


def load_env() -> dict[str, str]:
    env = {
        "CPANEL_HOST": "server27a.hostingraja.org",
        "CPANEL_PORT": "2083",
        "CPANEL_USER": "koloconi",
        "CPANEL_TOKEN": "",
    }
    if ENV_PATH.exists():
        for line in ENV_PATH.read_text().splitlines():
            line = line.strip()
            if not line or line.startswith("#") or "=" not in line:
                continue
            k, v = line.split("=", 1)
            env[k.strip()] = v.strip().strip('"').strip("'")
    for k in env:
        if os.environ.get(k):
            env[k] = os.environ[k]
    if not env["CPANEL_TOKEN"]:
        sys.exit(
            f"Missing CPANEL_TOKEN. Create ~/.config/dogalaxy/cpanel.env\n"
            f"See {ROOT / 'cpanel.env.example'}"
        )
    return env


def _ctx() -> ssl.SSLContext:
    # HostingRaja cPanel presents a chain macOS Python does not trust.
    return ssl._create_unverified_context()


def uapi(env: dict[str, str], module: str, func: str, **params):
    q = urllib.parse.urlencode(params)
    url = f"https://{env['CPANEL_HOST']}:{env['CPANEL_PORT']}/execute/{module}/{func}"
    if q:
        url += f"?{q}"
    req = urllib.request.Request(
        url,
        headers={"Authorization": f"cpanel {env['CPANEL_USER']}:{env['CPANEL_TOKEN']}"},
    )
    try:
        with urllib.request.urlopen(req, context=_ctx(), timeout=60) as r:
            data = json.loads(r.read().decode())
    except urllib.error.HTTPError as e:
        sys.exit(f"UAPI HTTP {e.code}: {e.read()[:400]!r}")
    except urllib.error.URLError as e:
        sys.exit(f"UAPI connect failed: {e.reason}")
    status = (data.get("status") if "status" in data else data.get("result", {}).get("status"))
    if status in (0, "0"):
        err = data.get("errors") or data.get("result", {}).get("errors")
        sys.exit(f"UAPI {module}::{func} failed: {err or data}")
    return data.get("result", data)


def api2(env: dict[str, str], module: str, func: str, **params):
    body = {
        "cpanel_jsonapi_user": env["CPANEL_USER"],
        "cpanel_jsonapi_apiversion": "2",
        "cpanel_jsonapi_module": module,
        "cpanel_jsonapi_func": func,
        **params,
    }
    url = f"https://{env['CPANEL_HOST']}:{env['CPANEL_PORT']}/json-api/cpanel"
    req = urllib.request.Request(
        url,
        data=urllib.parse.urlencode(body).encode(),
        headers={
            "Authorization": f"cpanel {env['CPANEL_USER']}:{env['CPANEL_TOKEN']}",
            "Content-Type": "application/x-www-form-urlencoded",
        },
        method="POST",
    )
    try:
        with urllib.request.urlopen(req, context=_ctx(), timeout=60) as r:
            return json.loads(r.read().decode())
    except urllib.error.HTTPError as e:
        sys.exit(f"API2 HTTP {e.code}: {e.read()[:400]!r}")


def upload(env: dict[str, str], local: Path, dest_dir: str, name: str | None = None):
    import uuid

    boundary = uuid.uuid4().hex
    filename = name or local.name
    file_bytes = local.read_bytes()
    parts = [
        f"--{boundary}\r\nContent-Disposition: form-data; name=\"dir\"\r\n\r\n{dest_dir}\r\n".encode(),
        (
            f"--{boundary}\r\n"
            f'Content-Disposition: form-data; name="file-1"; filename="{filename}"\r\n'
            f"Content-Type: application/octet-stream\r\n\r\n"
        ).encode()
        + file_bytes
        + b"\r\n"
        + f"--{boundary}--\r\n".encode(),
    ]
    body = b"".join(parts)
    url = f"https://{env['CPANEL_HOST']}:{env['CPANEL_PORT']}/execute/Fileman/upload_files"
    req = urllib.request.Request(
        url,
        data=body,
        headers={
            "Authorization": f"cpanel {env['CPANEL_USER']}:{env['CPANEL_TOKEN']}",
            "Content-Type": f"multipart/form-data; boundary={boundary}",
        },
        method="POST",
    )
    with urllib.request.urlopen(req, context=_ctx(), timeout=120) as r:
        data = json.loads(r.read().decode())
    if data.get("status") in (0, "0"):
        sys.exit(f"upload failed: {data.get('errors') or data}")
    return data


def cmd_ping(env, _):
    data = uapi(env, "Variables", "get_user_information")
    print(json.dumps(data.get("data", data), indent=2)[:2000])


def cmd_domains(env, _):
    data = uapi(env, "DomainInfo", "list_domains")
    print(json.dumps(data.get("data", data), indent=2))


def cmd_files(env, args):
    data = uapi(env, "Fileman", "list_files", dir=args.dir, include_mime=1)
    rows = data.get("data", data)
    if isinstance(rows, list):
        for f in rows:
            mark = "d" if f.get("type") == "dir" else "-"
            print(f"{mark} {f.get('file', f)}")
    else:
        print(json.dumps(rows, indent=2))


def cmd_call(env, args):
    params = dict(p.split("=", 1) for p in args.params)
    print(json.dumps(uapi(env, args.module, args.func, **params), indent=2))


def cmd_upload(env, args):
    local = Path(args.local).expanduser()
    print(json.dumps(upload(env, local, args.dest), indent=2)[:2000])


def cmd_deploy(env, args):
    products = json.loads((ROOT / "products.json").read_text())
    slug = args.slug
    if slug not in products:
        sys.exit(f"unknown slug: {slug}")
    p = products[slug]
    dest = f"{p['docroot']}/wp-content/themes"
    core = ROOT / "themes" / "dogalaxy-core"
    child = ROOT / "themes" / slug
    if not child.exists():
        sys.exit(f"missing theme {child}")
    with tempfile.TemporaryDirectory() as td:
        zpath = Path(td) / f"{slug}-themes.zip"
        with zipfile.ZipFile(zpath, "w", zipfile.ZIP_DEFLATED) as z:
            for src, arcroot in ((core, "dogalaxy-core"), (child, slug)):
                for f in src.rglob("*"):
                    if f.is_file():
                        z.write(f, f"{arcroot}/{f.relative_to(src)}")
        print(f"upload {zpath.name} → /home/koloconi/tmp")
        api2(env, "Fileman", "mkdir", path="/home/koloconi", name="tmp")
        upload(env, zpath, "/home/koloconi/tmp", f"{slug}-themes.zip")
    print(f"extract → {dest}")
    out = api2(
        env,
        "Fileman",
        "fileop",
        op="extract",
        sourcefiles=f"/home/koloconi/tmp/{slug}-themes.zip",
        destfiles=dest,
    )
    print(json.dumps(out, indent=2)[:3000])
    print(f"WP Admin → Appearance → activate {p['name']}")


def cmd_activate(env, args):
    """Force-activate child theme via a one-shot mu-plugin."""
    products = json.loads((ROOT / "products.json").read_text())
    slug = args.slug
    if slug not in products:
        sys.exit(f"unknown slug: {slug}")
    p = products[slug]
    mu = Path(p["docroot"]) / "wp-content" / "mu-plugins"
    php = f"""<?php
/**
 * Plugin Name: DoGalaxy activate {slug}
 */
add_action('init', function () {{
    if (get_option('dogalaxy_theme_forced') === '{slug}') {{
        return;
    }}
    if (!wp_get_theme('{slug}')->exists()) {{
        return;
    }}
    switch_theme('{slug}');
    update_option('dogalaxy_theme_forced', '{slug}');
}}, 0);
"""
    with tempfile.TemporaryDirectory() as td:
        f = Path(td) / f"dogalaxy-activate-{slug}.php"
        f.write_text(php)
        api2(env, "Fileman", "mkdir", path=str(Path(p["docroot"]) / "wp-content"), name="mu-plugins")
        upload(env, f, str(mu), f"dogalaxy-activate-{slug}.php")
    print(f"activation plugin uploaded for {slug}")


def cmd_app(env, args):
    """Deploy plugin + classic theme for a product slug (doudyog)."""
    slug = args.slug
    products = json.loads((ROOT / "products.json").read_text())
    if slug not in products:
        sys.exit(f"unknown slug: {slug}")
    p = products[slug]
    plugin = ROOT / "plugins" / f"{slug}-app"
    theme = ROOT / "themes" / slug
    if not plugin.exists() or not theme.exists():
        sys.exit(f"need {plugin} and {theme}")
    dest_themes = f"{p['docroot']}/wp-content/themes"
    dest_plugins = f"{p['docroot']}/wp-content/plugins"
    with tempfile.TemporaryDirectory() as td:
        zpath = Path(td) / f"{slug}-app.zip"
        with zipfile.ZipFile(zpath, "w", zipfile.ZIP_DEFLATED) as z:
            for src, arc in ((theme, f"themes/{slug}"), (plugin, f"plugins/{slug}-app")):
                for f in src.rglob("*"):
                    if f.is_file():
                        z.write(f, f"{arc}/{f.relative_to(src)}")
        api2(env, "Fileman", "mkdir", path="/home/koloconi", name="tmp")
        upload(env, zpath, "/home/koloconi/tmp", f"{slug}-app.zip")
    api2(env, "Fileman", "fileop", op="extract",
         sourcefiles=f"/home/koloconi/tmp/{slug}-app.zip",
         destfiles=f"{p['docroot']}/wp-content")
    php = f"""<?php
add_action('init', function () {{
    if (get_option('dogalaxy_app_boot_{slug}')) {{
        return;
    }}
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    activate_plugin('{slug}-app/{slug}-app.php');
    switch_theme('{slug}');
    $boot = '{slug}_activate';
    if (function_exists($boot)) {{
        $boot();
    }}
    update_option('dogalaxy_app_boot_{slug}', 1);
}}, 0);
"""
    with tempfile.TemporaryDirectory() as td:
        f = Path(td) / f"boot-{slug}.php"
        f.write_text(php)
        api2(env, "Fileman", "mkdir", path=f"{p['docroot']}/wp-content", name="mu-plugins")
        upload(env, f, f"{p['docroot']}/wp-content/mu-plugins", f"boot-{slug}.php")
    print(f"app deployed + bootstrapped: {slug}")


def cmd_phpapp(env, args):
    """Upload apps/<slug> to /home/koloconi/<domain>/app."""
    slug = args.slug
    products = json.loads((ROOT / "products.json").read_text())
    dest = f"{products[slug]['docroot']}/app"
    src = ROOT / "apps" / slug
    with tempfile.TemporaryDirectory() as td:
        zpath = Path(td) / f"{slug}-phpapp.zip"
        with zipfile.ZipFile(zpath, "w", zipfile.ZIP_DEFLATED) as z:
            for f in src.rglob("*"):
                if f.is_file() and f.name != "config.local.php":
                    z.write(f, f.relative_to(src))
        api2(env, "Fileman", "mkdir", path=products[slug]["docroot"], name="app")
        upload(env, zpath, "/home/koloconi/tmp", f"{slug}-phpapp.zip")
    api2(
        env,
        "Fileman",
        "fileop",
        op="extract",
        sourcefiles=f"/home/koloconi/tmp/{slug}-phpapp.zip",
        destfiles=dest,
    )
    print("php app →", dest)


def cmd_standalone(env, args):
    """Upload standalone MyDoApp to a cPanel directory."""
    src = ROOT / "standalone" / "mydoapp"
    dest = args.dest
    with tempfile.TemporaryDirectory() as td:
        zpath = Path(td) / "mydoapp-standalone.zip"
        with zipfile.ZipFile(zpath, "w", zipfile.ZIP_DEFLATED) as z:
            for f in src.rglob("*"):
                if f.is_file():
                    z.write(f, f.relative_to(src))
        api2(env, "Fileman", "mkdir", path="/home/koloconi", name="tmp")
        upload(env, zpath, "/home/koloconi/tmp", "mydoapp-standalone.zip")
    api2(env, "Fileman", "mkdir", path="/home/koloconi", name=Path(dest).name)
    api2(
        env,
        "Fileman",
        "fileop",
        op="extract",
        sourcefiles="/home/koloconi/tmp/mydoapp-standalone.zip",
        destfiles=dest,
    )
    print("standalone MyDoApp →", dest)


def cmd_git_clone(env, _):
    out = uapi(
        env,
        "VersionControl",
        "create",
        repository_root="/home/koloconi/DoGalaxy",
        type="git",
        name="DoGalaxy",
        **{
            "source_repository[url]": "https://github.com/DrDeveshK/DoGalaxy.git",
            "source_repository[name]": "origin",
        },
    )
    print(json.dumps(out, indent=2)[:4000])


def main():
    ap = argparse.ArgumentParser(description="cPanel UAPI for DoGalaxy")
    sub = ap.add_subparsers(dest="cmd", required=True)
    sub.add_parser("ping")
    sub.add_parser("domains")
    p = sub.add_parser("files")
    p.add_argument("dir", nargs="?", default="/home/koloconi")
    p = sub.add_parser("call")
    p.add_argument("module")
    p.add_argument("func")
    p.add_argument("params", nargs="*")
    p = sub.add_parser("upload")
    p.add_argument("local")
    p.add_argument("dest")
    p = sub.add_parser("deploy")
    p.add_argument("slug")
    p = sub.add_parser("activate")
    p.add_argument("slug")
    p = sub.add_parser("app")
    p.add_argument("slug")
    p = sub.add_parser("phpapp")
    p.add_argument("slug")
    p = sub.add_parser("standalone")
    p.add_argument("dest")
    sub.add_parser("git-clone")
    args = ap.parse_args()
    env = load_env()
    {
        "ping": cmd_ping,
        "domains": cmd_domains,
        "files": cmd_files,
        "call": cmd_call,
        "upload": cmd_upload,
        "deploy": cmd_deploy,
        "activate": cmd_activate,
        "app": cmd_app,
        "phpapp": cmd_phpapp,
        "standalone": cmd_standalone,
        "git-clone": cmd_git_clone,
    }[args.cmd](env, args)


if __name__ == "__main__":
    main()
