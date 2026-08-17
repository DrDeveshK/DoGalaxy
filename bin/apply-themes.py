#!/usr/bin/env python3
"""Write a light, bright, name-led palette into each product app.css."""
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
BASE = (ROOT / "apps/_platform/assets/base.css").read_text(encoding="utf-8")

# Light modern palettes — hero stays readable, page stays airy.
THEMES = {
    "doudyog": """/* DoUdyog — industry saffron + open sky */
:root{--navy:#0b4f9c;--blue:#2b7de9;--orange:#ff8a00;--soft:#eef5ff;--page:#f7fbff;--text:#122033;--muted:#5b6b80;--green:#14a46c;--border:#d5e4f6;--topbar-bg:#e8f1ff;--topbar-text:#0b4f9c;--hero-from:#dbebff;--hero-to:#7eb3ff;--hero-spot:rgba(255,138,0,.35);--hero-text:#071f3d;--hero-muted:#1d3a66;--chip-bg:rgba(255,255,255,.55);--chip-border:rgba(11,79,156,.2);--stat-bg:rgba(255,255,255,.45);--icon-bg:#fff3e4;--footer-bg:#0b4f9c;--footer-text:#dbe8ff}""",
    "dovishram": """/* DoVishram — rest: mint, leaf, warm sand */
:root{--navy:#1a7a5c;--blue:#2ec99a;--orange:#e8a317;--soft:#eefaf4;--page:#f7fffb;--text:#163028;--muted:#5a7368;--green:#1aa36a;--border:#cfe8dc;--topbar-bg:#e4f7ee;--topbar-text:#1a7a5c;--hero-from:#e3f8ee;--hero-to:#7ee0b8;--hero-spot:rgba(232,163,23,.35);--hero-text:#0f3d2e;--hero-muted:#245544;--chip-bg:rgba(255,255,255,.6);--chip-border:rgba(26,122,92,.2);--stat-bg:rgba(255,255,255,.45);--icon-bg:#fff6dd;--footer-bg:#1a7a5c;--footer-text:#e4f7ee}""",
    "dorojgar": """/* DoRojgar — work: electric teal + amber */
:root{--navy:#0d6e8a;--blue:#12b5d4;--orange:#ffb300;--soft:#e7f8fc;--page:#f4fcfe;--text:#12303a;--muted:#56707a;--green:#14a46c;--border:#c5e8f0;--topbar-bg:#d9f4fa;--topbar-text:#0d6e8a;--hero-from:#d4f4fb;--hero-to:#4fd0e8;--hero-spot:rgba(255,179,0,.4);--hero-text:#083848;--hero-muted:#1b4d5c;--chip-bg:rgba(255,255,255,.6);--chip-border:rgba(13,110,138,.2);--stat-bg:rgba(255,255,255,.45);--icon-bg:#fff6d9;--footer-bg:#0d6e8a;--footer-text:#d9f4fa}""",
    "doswagat": """/* DoSwagat — welcome: coral, marigold, festivity */
:root{--navy:#c43c2c;--blue:#ff6b4a;--orange:#ffb020;--soft:#fff4ee;--page:#fff9f5;--text:#3a1c14;--muted:#8a5a4e;--green:#2f9e6a;--border:#f3d4c6;--topbar-bg:#ffe8dc;--topbar-text:#c43c2c;--hero-from:#ffe7d6;--hero-to:#ff8a6a;--hero-spot:rgba(255,176,32,.45);--hero-text:#4a160c;--hero-muted:#6a2a1c;--chip-bg:rgba(255,255,255,.55);--chip-border:rgba(196,60,44,.2);--stat-bg:rgba(255,255,255,.4);--icon-bg:#fff1c9;--footer-bg:#c43c2c;--footer-text:#ffe8dc}""",
    "dorishta": """/* DoRishta — family: blush rose + warm gold (not dating neon) */
:root{--navy:#9a3d72;--blue:#d46aa8;--orange:#d4a017;--soft:#fdf4f9;--page:#fff8fb;--text:#3a1f30;--muted:#7a5a6c;--green:#2f9e6a;--border:#f0d0e2;--topbar-bg:#fbe6f1;--topbar-text:#9a3d72;--hero-from:#fde8f3;--hero-to:#e89ac4;--hero-spot:rgba(212,160,23,.4);--hero-text:#4a1836;--hero-muted:#6a2a4e;--chip-bg:rgba(255,255,255,.6);--chip-border:rgba(154,61,114,.2);--stat-bg:rgba(255,255,255,.45);--icon-bg:#fff4cc;--footer-bg:#9a3d72;--footer-text:#fbe6f1}""",
    "dobajar": """/* DoBajar — bazaar: mango, turmeric, terracotta */
:root{--navy:#c05600;--blue:#f5a201;--orange:#ef6c00;--soft:#fff6e8;--page:#fffaf0;--text:#3a2408;--muted:#8a6a3a;--green:#2f9e6a;--border:#f0d7a8;--topbar-bg:#ffe9b8;--topbar-text:#c05600;--hero-from:#fff0c8;--hero-to:#ffb347;--hero-spot:rgba(239,108,0,.35);--hero-text:#4a2a00;--hero-muted:#6a4208;--chip-bg:rgba(255,255,255,.55);--chip-border:rgba(192,86,0,.2);--stat-bg:rgba(255,255,255,.4);--icon-bg:#ffe0c2;--footer-bg:#c05600;--footer-text:#ffe9b8}""",
    "doaaram": """/* DoAaram — ease: lilac sky + spa teal */
:root{--navy:#4d5fd4;--blue:#8ea2ff;--orange:#3ec6c0;--soft:#f0f2ff;--page:#f7f8ff;--text:#1e2448;--muted:#5c6488;--green:#2f9e6a;--border:#d5dbf6;--topbar-bg:#e4e8ff;--topbar-text:#4d5fd4;--hero-from:#e8ecff;--hero-to:#9eb0ff;--hero-spot:rgba(62,198,192,.4);--hero-text:#1a2050;--hero-muted:#323a72;--chip-bg:rgba(255,255,255,.6);--chip-border:rgba(77,95,212,.2);--stat-bg:rgba(255,255,255,.45);--icon-bg:#d9f7f5;--footer-bg:#4d5fd4;--footer-text:#e4e8ff}""",
    "donirman": """/* DoNirman — build: sky blue + terracotta brick */
:root{--navy:#2f6fad;--blue:#5aa6e8;--orange:#e07a3d;--soft:#eef5fb;--page:#f6fafd;--text:#1a2c3c;--muted:#5a6e80;--green:#2f9e6a;--border:#cddcea;--topbar-bg:#dceaf6;--topbar-text:#2f6fad;--hero-from:#e3f1fb;--hero-to:#7ab8ea;--hero-spot:rgba(224,122,61,.4);--hero-text:#163044;--hero-muted:#2a4a62;--chip-bg:rgba(255,255,255,.55);--chip-border:rgba(47,111,173,.2);--stat-bg:rgba(255,255,255,.45);--icon-bg:#ffe8d8;--footer-bg:#2f6fad;--footer-text:#dceaf6}""",
    "dovyapaar": """/* DoVyapaar — trade: emerald + coin gold */
:root{--navy:#0d7a4f;--blue:#22b07a;--orange:#e8b020;--soft:#e8f8f0;--page:#f4fcf7;--text:#123024;--muted:#547064;--green:#1aa36a;--border:#c5e6d4;--topbar-bg:#d6f3e4;--topbar-text:#0d7a4f;--hero-from:#d9f5e6;--hero-to:#4fd197;--hero-spot:rgba(232,176,32,.4);--hero-text:#0a3a26;--hero-muted:#1c5540;--chip-bg:rgba(255,255,255,.55);--chip-border:rgba(13,122,79,.2);--stat-bg:rgba(255,255,255,.45);--icon-bg:#fff4cc;--footer-bg:#0d7a4f;--footer-text:#d6f3e4}""",
    "mydoapp": """/* MyDoApp — galaxy door: violet + orchid */
:root{--navy:#5b3cc4;--blue:#8b6cff;--orange:#ff6b9d;--soft:#f3efff;--page:#f8f6ff;--text:#241848;--muted:#6a6088;--green:#2f9e6a;--border:#ddd4f6;--topbar-bg:#ebe4ff;--topbar-text:#5b3cc4;--hero-from:#efe8ff;--hero-to:#b49cff;--hero-spot:rgba(255,107,157,.35);--hero-text:#2a1460;--hero-muted:#3e2a78;--chip-bg:rgba(255,255,255,.6);--chip-border:rgba(91,60,196,.2);--stat-bg:rgba(255,255,255,.45);--icon-bg:#ffe4ee;--footer-bg:#5b3cc4;--footer-text:#ebe4ff}""",
}


def main() -> None:
    for slug, theme in THEMES.items():
        css = theme.strip() + "\n" + BASE
        path = ROOT / "apps" / slug / "assets" / "app.css"
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(css, encoding="utf-8")
        print("themed", slug)


if __name__ == "__main__":
    main()
