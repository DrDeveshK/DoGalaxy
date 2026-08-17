#!/usr/bin/env python3
"""Generate original-family (navy/orange) MVP themes for Do* products."""
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
CSS = (ROOT / "design" / "udyog.css").read_text()

PRODUCTS = {
    "dovishram": {
        "name": "DoVishram",
        "mark": "V",
        "eyebrow": "विश्राम मिले, मन सुहे",
        "h1": "Find a trusted stay. List your rooms.",
        "lede": "DoVishram is the stays planet of Do Galaxy — hotels, homestays and short stays with a clear request flow.",
        "cta": ("List a stay", "/join"),
        "cta2": ("Explore stays", "/stays"),
        "search": ("Find a stay", "/stays", "City, dates, type of stay..."),
        "nav": [("Home", "/"), ("Stays", "/stays"), ("Join", "/join")],
        "features": [
            ("🛏️", "Stay identity", "A verified listing for your hotel, homestay or rooms."),
            ("📅", "Date requests", "Guests request dates. You accept or decline."),
            ("✅", "Trust signals", "City, type and verification on every card."),
            ("👨‍👩‍👧", "Family stays", "Clear rooms for families and working travellers."),
            ("🔗", "Do Galaxy", "Connect guests to events, jobs and local trade."),
            ("🌌", "MyDoApp ready", "One identity across the Do planets."),
        ],
        "footer": "Trusted stays and rest under Do Galaxy.",
    },
    "dorojgar": {
        "name": "DoRojgar",
        "mark": "R",
        "eyebrow": "रोज़गार मिले, सम्मान रहे",
        "h1": "Post a job. Apply locally. Hire with trust.",
        "lede": "DoRojgar is the employment planet of Do Galaxy — hyperlocal jobs for seekers and a simple hiring channel for MSMEs.",
        "cta": ("Post a job", "/join"),
        "cta2": ("Browse jobs", "/jobs"),
        "search": ("Find work", "/jobs", "Role, city, skill..."),
        "nav": [("Home", "/"), ("Jobs", "/jobs"), ("Join", "/join")],
        "features": [
            ("💼", "Job posts", "Employers post roles against a real business identity."),
            ("🙋", "Apply in one flow", "Seekers apply and track the outcome."),
            ("📍", "Hyperlocal", "City-first matching for frontline and SME roles."),
            ("🏢", "DoUdyog link", "Hire from a verified Udyog profile."),
            ("📋", "Simple tracking", "Open, shortlisted, hired — visible to both sides."),
            ("🌌", "Do Galaxy", "Work connects to stays, events and trade."),
        ],
        "footer": "Local work and hiring under Do Galaxy.",
    },
    "doswagat": {
        "name": "DoSwagat",
        "mark": "S",
        "eyebrow": "स्वागत हो, उत्सव बने",
        "h1": "Book a venue. Brief a partner. Host with confidence.",
        "lede": "DoSwagat is the events planet of Do Galaxy — weddings, celebrations and corporate hospitality in one coordinated flow.",
        "cta": ("List a venue", "/join"),
        "cta2": ("Find venues", "/venues"),
        "search": ("Find a venue", "/venues", "City, guest count, event type..."),
        "nav": [("Home", "/"), ("Venues", "/venues"), ("Services", "/services"), ("Join", "/join")],
        "features": [
            ("🏛️", "Venues", "Halls, lawns and rooms with capacity and city."),
            ("🎉", "Partners", "Catering, décor, photography — one brief."),
            ("📦", "Packages", "Ready event packages you can request."),
            ("📝", "Request flow", "Send a brief and track the response."),
            ("🤝", "Hospitality", "Welcome guests the Do Galaxy way."),
            ("🌌", "Connected", "Stays via Vishram, staff via Rojgar."),
        ],
        "footer": "Events and welcome under Do Galaxy.",
    },
    "dorishta": {
        "name": "DoRishta",
        "mark": "Ri",
        "eyebrow": "रिश्ता बने, विश्वास रहे",
        "h1": "A family-friendly path to a meaningful match.",
        "lede": "DoRishta is the matrimony planet of Do Galaxy — verified profiles, 21+, and respectful interest. No casual dating.",
        "cta": ("Create profile", "/join"),
        "cta2": ("How it works", "/contact"),
        "search": ("Find a profile", "/join", "City, community, intent..."),
        "nav": [("Home", "/"), ("Join", "/join"), ("Stories", "/stories"), ("Contact", "/contact")],
        "features": [
            ("🪪", "Verified profiles", "Identity-first. Adults 21 and above only."),
            ("👨‍👩‍👧", "Family-friendly", "Parents can participate with dignity."),
            ("💬", "Interest, not chat spam", "Express interest. The other side accepts."),
            ("🔒", "Privacy", "What you share is what they see."),
            ("📍", "Regional depth", "City and community that actually match."),
            ("🌌", "Do Galaxy", "A serious planet beside work, stay and trade."),
        ],
        "footer": "Trusted, family-friendly matches under Do Galaxy.",
    },
    "dobajar": {
        "name": "DoBajar",
        "mark": "B",
        "eyebrow": "बाज़ार खुले, व्यापार बढ़े",
        "h1": "List products. Get found. Sell locally.",
        "lede": "DoBajar is the marketplace planet of Do Galaxy — seller visibility and product discovery for everyday buyers.",
        "cta": ("Become a seller", "/join"),
        "cta2": ("Browse listings", "/listings"),
        "search": ("Find products", "/listings", "Product, city, category..."),
        "nav": [("Home", "/"), ("Listings", "/listings"), ("Join", "/join")],
        "features": [
            ("🛒", "Listings", "A public card for what you sell."),
            ("🏪", "Seller identity", "Tied to DoUdyog when the firm is verified."),
            ("🔍", "Discovery", "City and category search."),
            ("📩", "Enquiries", "Buyers request. You respond."),
            ("📦", "Local first", "Neighbourhood trade before national ads."),
            ("🌌", "Do Galaxy", "From identity to customer in one universe."),
        ],
        "footer": "Local marketplace under Do Galaxy.",
    },
    "doaaram": {
        "name": "DoAaram",
        "mark": "A",
        "eyebrow": "आराम मिले, काम बने",
        "h1": "Everyday services, close to home.",
        "lede": "DoAaram is the local-services planet of Do Galaxy — day-to-day help you can request with a clear identity.",
        "cta": ("Offer a service", "/join"),
        "cta2": ("Find a service", "/services"),
        "search": ("Find help", "/services", "Service, city..."),
        "nav": [("Home", "/"), ("Services", "/services"), ("Join", "/join")],
        "features": [
            ("🧰", "Local services", "Plumbers, tutors, drivers, home help."),
            ("📍", "Near you", "City-first, not a national dump."),
            ("✅", "Identity", "Providers linked to a Do profile."),
            ("📩", "Request", "Send a need. Track the reply."),
            ("🤝", "Trust", "Simple ratings and verification state."),
            ("🌌", "Do Galaxy", "Services beside jobs, stays and trade."),
        ],
        "footer": "Everyday local services under Do Galaxy.",
    },
}


def theme_files(slug: str, p: dict) -> dict[str, str]:
    css = CSS.replace("DoUdyog Working MVP", f"{p['name']} Working MVP", 1)
    css = css.replace("https://doudyog.com", f"https://{slug}.com", 1)
    css = css.replace("doudyog", slug)
    nav = "".join(f'<a href="{href}">{lab}</a>' for lab, href in p["nav"])
    feats = "".join(
        f'<div class="feature"><div class="icon">{ic}</div><h3>{t}</h3><p>{d}</p></div>'
        for ic, t, d in p["features"]
    )
    header = f"""<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<div class="topbar"><div class="container">
  <span>{p['name']} — {p['footer']}</span>
  <span>Powered by Kusumit Universe · MyDoApp</span>
</div></div>
<header class="site-header"><div class="container header-inner">
  <a class="brand" href="<?php echo esc_url(home_url('/')); ?>">
    <span class="brand-mark">{p['mark'][0]}<span>o</span></span><span>{p['name']}</span>
  </a>
  <nav class="nav" id="mainNav">{nav}</nav>
  <a class="btn" href="{p['cta'][1]}">{p['cta'][0]}</a>
</div></header>
"""
    footer = f"""<footer class="footer"><div class="container footer-grid">
  <div><h3>{p['name']}</h3><p>{p['footer']}</p></div>
  <div><h4>Platform</h4>{''.join(f'<a href="{h}">{l}</a>' for l,h in p['nav'])}</div>
  <div><h4>Do Galaxy</h4>
    <a href="https://mydoapp.com">MyDoApp</a>
    <a href="https://doudyog.com">DoUdyog</a>
    <a href="https://dorojgar.com">DoRojgar</a>
    <a href="https://dovishram.com">DoVishram</a>
    <a href="https://doswagat.com">DoSwagat</a>
    <a href="https://dorishta.com">DoRishta</a>
    <a href="https://dobajar.com">DoBajar</a>
  </div>
  <div><h4>Company</h4><a href="/join">Join</a><a href="/contact">Contact</a><a href="https://kusumit.com">Kusumit</a></div>
</div>
<div class="container" style="border-top:1px solid rgba(255,255,255,.12);margin-top:28px;padding-top:18px;color:#91a8c8">© <?php echo date('Y'); ?> {p['name']}. A Kusumit Universe initiative.</div>
</footer>
<?php wp_footer(); ?></body></html>
"""
    front = f"""<?php get_header(); ?>
<section class="hero"><div class="container hero-grid"><div>
  <span class="eyebrow">{p['eyebrow']}</span>
  <h1>{p['h1']}</h1>
  <p>{p['lede']}</p>
  <div class="hero-actions">
    <a class="btn" href="{p['cta'][1]}">{p['cta'][0]}</a>
    <a class="btn light" href="{p['cta2'][1]}">{p['cta2'][0]}</a>
  </div>
</div>
<div class="search-panel">
  <h3>{p['search'][0]}</h3>
  <form action="{p['search'][1]}" method="get">
    <input class="input" name="q" placeholder="{p['search'][2]}">
    <br><br><button class="btn" type="submit">Search</button>
  </form>
</div></div></section>
<section class="section"><div class="container">
  <div class="section-title"><div><h2>What {p['name']} does</h2><p>{p['lede']}</p></div></div>
  <div class="grid-3">{feats}</div>
</div></section>
<?php get_footer(); ?>
"""
    functions = f"""<?php
if (!defined('ABSPATH')) {{ exit; }}
add_action('after_setup_theme', function () {{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
}});
add_action('wp_enqueue_scripts', function () {{
    wp_enqueue_style('{slug}-mvp', get_stylesheet_uri(), array(), '1.0.0');
}});
"""
    index = "<?php get_header(); ?><?php if (have_posts()) { while (have_posts()) { the_post(); the_title('<h1>','</h1>'); the_content(); } } get_footer();"
    return {
        "style.css": css,
        "header.php": header,
        "footer.php": footer,
        "front-page.php": front,
        "functions.php": functions,
        "index.php": index,
    }


def standalone(slug: str, p: dict) -> str:
    """Single-file site for domains without WordPress (dobajar)."""
    files = theme_files(slug, p)
    css = files["style.css"]
    # strip WP tags
    head = files["header.php"]
    for a, b in [
        ("<?php language_attributes(); ?>", ""),
        ("<?php wp_head(); ?>", ""),
        ("<?php body_class(); ?>", ""),
        ("<?php echo esc_url(home_url('/')); ?>", "/"),
    ]:
        head = head.replace(a, b)
    foot = files["footer.php"].replace("<?php echo date('Y'); ?>", "2026").replace("<?php wp_footer(); ?>", "")
    body = files["front-page.php"].replace("<?php get_header(); ?>", "").replace("<?php get_footer(); ?>", "")
    return f"""<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>{p['name']}</title>
<style>{css}</style>
</head>
{head}
{body}
{foot}
"""


def main() -> None:
    out = ROOT / "themes"
    for slug, p in PRODUCTS.items():
        dest = out / f"{slug}-mvp"
        dest.mkdir(parents=True, exist_ok=True)
        for name, content in theme_files(slug, p).items():
            (dest / name).write_text(content)
        print("wrote", dest)
    (ROOT / "standalone" / "dobajar" / "index.php").write_text(standalone("dobajar", PRODUCTS["dobajar"]))
    print("wrote standalone/dobajar/index.php")


if __name__ == "__main__":
    main()
