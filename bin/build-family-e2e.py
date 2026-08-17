#!/usr/bin/env python3
"""Write product.php + app shell for every Do Galaxy vertical except Udyog."""
from __future__ import annotations

import shutil
from pathlib import Path
from textwrap import dedent

ROOT = Path(__file__).resolve().parents[1]
CSS = ROOT / "apps" / "doudyog" / "assets" / "app.css"

LEGAL = {
    "privacy": "We store the account, listing, documents and requests you submit. Staff see them to verify and support you. We do not sell your data. Write to the contact email to request deletion.",
    "terms": "This is a listing and operating tool, not a government portal and not legal advice. You are responsible for the accuracy of what you enter. Verification is a human review. Paid packages are fulfilled after staff accept the request.",
}

INDEX = dedent(
    """\
    <?php
    declare(strict_types=1);
    define('DG_APP', __DIR__);
    require dirname(__DIR__) . '/_platform/boot.php';
    require dirname(__DIR__) . '/_platform/engine.php';
    run_app();
    """
)

INSTALL = dedent(
    """\
    <?php
    declare(strict_types=1);
    if (($_GET['key'] ?? '') !== 'dogalaxy') { http_response_code(403); exit('forbidden'); }
    $cands = [dirname(__DIR__, 2) . '/wp-config.php', dirname(DG_APP ?? __DIR__, 2) . '/wp-config.php'];
    $wp = '';
    foreach ([dirname(__DIR__) . '/wp-config.php', '/home/koloconi/' . basename(dirname(__DIR__)) . '/wp-config.php'] as $c) {
      if (is_file($c)) { $wp = $c; break; }
    }
    if ($wp === '') exit('wp-config not found');
    $src = (string) file_get_contents($wp);
    preg_match("/DB_NAME',\\s*'([^']+)'/", $src, $n);
    preg_match("/DB_USER',\\s*'([^']+)'/", $src, $u);
    preg_match("/DB_PASSWORD',\\s*'([^']+)'/", $src, $p);
    preg_match("/DB_HOST',\\s*'([^']+)'/", $src, $h);
    if (!$n || !$u || !$p) exit('cannot parse wp-config');
    file_put_contents(__DIR__ . '/config.local.php', "<?php\\nreturn ['dsn'=>'mysql:host={$h[1]};dbname={$n[1]};charset=utf8mb4','user'=>{$u[1]},'pass'=>{$p[1]}];\\n");
    echo 'ok — delete install.php';
    """
)
# fix install - don't use broken quoting. Write a proper PHP file instead.

HTACCESS = "DirectoryIndex index.php\n<Files \"config.local.php\">\n  Require all denied\n</Files>\n<Files \"product.php\">\n  Require all denied\n</Files>\n<Files \"boot.php\">\n  Require all denied\n</Files>\n"

PRODUCTS: dict[str, dict] = {}

PRODUCTS["dovishram"] = {
    "brand": "DoVishram",
    "mark": "V",
    "topbar": "DoVishram — Trusted stays, short breaks and rest",
    "eyebrow": "विश्राम मिले, मन हरे",
    "hero_h1": "Find a trusted stay. Host with a verified listing.",
    "hero_p": "DoVishram is the rest planet of Do Galaxy. Discover homestays, hotels and short breaks, or list your property and take booking requests.",
    "footer_blurb": "Trusted stays and curated rest under Do Galaxy.",
    "page_about": "DoVishram helps travellers and families find verified stays, and helps hosts take structured booking requests.",
    "join_cta": "List a stay",
    "dir_label": "Stays",
    "owner_role": "host",
    "listing_label": "Stay",
    "listing_table": "dg_stays",
    "owner_col": "host_id",
    "title_col": "title",
    "status_col": "verify_status",
    "listing_mode": "many",
    "request_table": "dg_stay_requests",
    "request_fk": "stay_id",
    "request_label": "Booking request",
    "fields": [
        ("title", "Stay name", "text", True),
        ("stay_type", "Type", "select", True, ["Homestay", "Hotel", "Resort", "Guest house", "Wellness retreat"]),
        ("city", "City", "text", True),
        ("price_night", "Price / night", "text", False),
        ("max_guests", "Max guests", "text", False),
        ("about", "About the stay", "textarea", False),
    ],
    "request_fields": [
        ("checkin", "Check-in", "date", True),
        ("checkout", "Check-out", "date", True),
        ("guests", "Guests", "number", True),
        ("message", "Message", "textarea", True),
    ],
    "docs": {"id": "Host ID", "property": "Property proof", "safety": "Safety note", "gst": "GST if any"},
    "packages": "Stay listing setup | ₹999 | Verified host profile.\nWeekend rest package | ₹1,999 | Curated 2-night stay help.\nHost onboarding | ₹2,499 | Photos, house rules, calendar.",
    "seed": [
        ("Quiet Courtyard Homestay", "Homestay", "Jaipur", "₹2,400", "4", "Family rooms around a courtyard."),
        ("Lakeview Rest House", "Guest house", "Udaipur", "₹3,200", "6", "Short stays by the lake."),
        ("Hill Pine Retreat", "Resort", "Nainital", "₹5,500", "8", "Pines, tea and early nights."),
        ("City Pause Rooms", "Hotel", "Pune", "₹1,800", "2", "Clean rooms for business rest."),
    ],
}

PRODUCTS["dorojgar"] = {
    "brand": "DoRojgar",
    "mark": "R",
    "topbar": "DoRojgar — Local jobs and hiring",
    "eyebrow": "काम मिले, मान बढ़े",
    "hero_h1": "Find local work. Hire people you can meet.",
    "hero_p": "DoRojgar is the employment planet of Do Galaxy. Employers post roles. Seekers apply with a short note. Staff can verify employers.",
    "footer_blurb": "Hyperlocal jobs for shops, factories, offices and gigs.",
    "page_about": "DoRojgar connects local employers with seekers. It is a hiring desk, not a gig-only app.",
    "join_cta": "Post a job",
    "dir_label": "Jobs",
    "owner_role": "employer",
    "listing_label": "Job",
    "listing_table": "dg_jobs",
    "owner_col": "employer_id",
    "title_col": "title",
    "status_col": "status",
    "listing_mode": "many",
    "request_table": "dg_applications",
    "request_fk": "job_id",
    "request_label": "Application",
    "fields": [
        ("title", "Role title", "text", True),
        ("job_type", "Type", "select", True, ["Full-time", "Part-time", "Gig", "Contract", "Apprentice"]),
        ("city", "City", "text", True),
        ("pay", "Pay", "text", False),
        ("description", "Description", "textarea", True),
    ],
    "request_fields": [
        ("experience", "Experience", "text", False),
        ("message", "Why you fit", "textarea", True),
    ],
    "docs": {"gst": "Employer GST / Udyam", "id": "ID proof", "address": "Workplace address"},
    "packages": "Employer profile | ₹999 | Verified hiring desk.\nHiring kit | ₹1,499 | Role templates and shortlist help.\nWorkforce sprint | ₹2,999 | Multi-role hiring support.",
    "seed": [
        ("Shop floor supervisor", "Full-time", "Pune", "₹22,000 / mo", "Day shift, 6 days, MSME factory."),
        ("Delivery associate", "Gig", "Jaipur", "₹500 / day", "Local routes, own bike preferred."),
        ("Front desk executive", "Full-time", "Indore", "₹18,000 / mo", "Hotel and clinic walk-ins."),
        ("Accounts assistant", "Part-time", "Delhi", "₹12,000 / mo", "GST entries and invoicing."),
    ],
}

PRODUCTS["doswagat"] = {
    "brand": "DoSwagat",
    "mark": "S",
    "topbar": "DoSwagat — Venues, events and hospitality",
    "eyebrow": "स्वागत हो, उत्सव हो",
    "hero_h1": "Plan the event. Book the venue and the team.",
    "hero_p": "DoSwagat is the welcome planet of Do Galaxy. Halls, caterers and hospitality partners take structured event requests.",
    "footer_blurb": "Weddings, parties and corporate events in one desk.",
    "page_about": "DoSwagat organises venues and hospitality partners so families and firms can request a date, guest count and brief in one place.",
    "join_cta": "List a venue",
    "dir_label": "Venues",
    "owner_role": "partner",
    "listing_label": "Venue",
    "listing_table": "dg_venues",
    "owner_col": "partner_id",
    "title_col": "title",
    "status_col": "verify_status",
    "listing_mode": "many",
    "request_table": "dg_event_requests",
    "request_fk": "venue_id",
    "request_label": "Event request",
    "fields": [
        ("title", "Venue / service name", "text", True),
        ("kind", "Kind", "select", True, ["Banquet", "Lawn", "Hotel", "Caterer", "Decorator", "Photographer"]),
        ("city", "City", "text", True),
        ("capacity", "Capacity", "text", False),
        ("about", "About", "textarea", False),
    ],
    "request_fields": [
        ("event_date", "Event date", "date", True),
        ("guests", "Guests", "number", False),
        ("event_type", "Event type", "text", False),
        ("message", "Brief", "textarea", True),
    ],
    "docs": {"licence": "Trade licence", "fire": "Fire NOC", "gst": "GST", "menu": "Menu / rate card"},
    "packages": "Venue listing | ₹1,499 | Verified hall profile.\nWedding desk | ₹4,999 | Venue + caterer + decor shortlist.\nCorporate meet | ₹2,999 | Half-day package help.",
    "seed": [
        ("Saffron Lawn", "Lawn", "Jaipur", "400", "Open lawn with stage and kitchen."),
        ("Pearl Banquet", "Banquet", "Indore", "250", "AC hall, in-house catering."),
        ("Swagat Kitchen", "Caterer", "Pune", "800", "North and Gujarati thali service."),
        ("Lens & Light Studio", "Photographer", "Delhi", "—", "Weddings and corporate stills."),
    ],
}

PRODUCTS["dorishta"] = {
    "brand": "DoRishta",
    "mark": "R",
    "topbar": "DoRishta — Family-friendly matrimonial discovery · 21+",
    "eyebrow": "रिश्ता परिवार के साथ",
    "hero_h1": "A family-friendly path to a life partner.",
    "hero_p": "DoRishta is for adults 21 and over, with families involved. Verified profiles, expressions of interest, no dating feed.",
    "footer_blurb": "Trusted, family-first matrimonial discovery. Not a dating app.",
    "page_about": "DoRishta is a family-friendly matrimonial platform for adults 21+. It is not dating, not casual, and not for anyone under 21.",
    "page_terms": "You must be 21 or older. Profiles are for marriage enquiry with family knowledge. Misuse, under-age accounts and dating-style solicitation are removed.",
    "join_cta": "Create profile",
    "dir_label": "Profiles",
    "owner_role": "member",
    "listing_label": "Profile",
    "listing_table": "dg_profiles",
    "owner_col": "user_id",
    "title_col": "display_name",
    "status_col": "verify_status",
    "listing_mode": "one",
    "age_min": 21,
    "request_table": "dg_interests",
    "request_fk": "to_profile_id",
    "request_label": "Expression of interest",
    "fields": [
        ("display_name", "Display name", "text", True),
        ("birth_date", "Date of birth", "date", True),
        ("city", "City", "text", True),
        ("community", "Community (optional)", "text", False),
        ("about", "About the family / self", "textarea", False),
    ],
    "request_fields": [
        ("note", "Note to the family", "textarea", True),
    ],
    "docs": {"id": "Government ID (21+)", "address": "Address proof", "family": "Family introduction letter"},
    "packages": "Verified profile | ₹999 | ID review by staff.\nFamily introduction | ₹1,499 | Guided first note.\nMembership | ₹2,999 | Priority listing for 90 days.",
    "seed": [
        ("Asha S.", "1998-03-12", "Pune", "Open", "Teacher. Family in Pune. Looking for a kind, working partner."),
        ("Rahul M.", "1996-07-21", "Jaipur", "Open", "Runs a shop with parents. Values honesty and family time."),
        ("Neha I.", "1999-11-02", "Delhi", "Open", "CA article. Families may write first."),
        ("Imran K.", "1995-01-30", "Indore", "Open", "Construction business. Prefers a family meeting first."),
    ],
}

PRODUCTS["dobajar"] = {
    "brand": "DoBajar",
    "mark": "B",
    "topbar": "DoBajar — Local marketplace for sellers and buyers",
    "eyebrow": "बाज़ार डिजिटल, भरोसा स्थानीय",
    "hero_h1": "List what you sell. Let buyers request an order.",
    "hero_p": "DoBajar is the commerce planet of Do Galaxy. Sellers from DoUdyog get a storefront. Buyers send quantity and a message — staff and sellers close the loop.",
    "footer_blurb": "Seller visibility and order requests for local trade.",
    "page_about": "DoBajar is a request-based marketplace. It is not instant checkout yet — sellers accept or decline each order request.",
    "join_cta": "Start selling",
    "dir_label": "Listings",
    "owner_role": "seller",
    "listing_label": "Product",
    "listing_table": "dg_listings",
    "owner_col": "seller_id",
    "title_col": "title",
    "status_col": "status",
    "listing_mode": "many",
    "request_table": "dg_order_requests",
    "request_fk": "listing_id",
    "request_label": "Order request",
    "fields": [
        ("title", "Product name", "text", True),
        ("category", "Category", "select", True, ["Grocery", "Textiles", "Hardware", "Food", "Craft", "Other"]),
        ("city", "City", "text", True),
        ("price", "Price", "text", False),
        ("about", "Description", "textarea", False),
    ],
    "request_fields": [
        ("qty", "Quantity", "number", True),
        ("message", "Message", "textarea", True),
    ],
    "docs": {"gst": "GSTIN", "photo": "Product photo sheet", "address": "Pickup address"},
    "packages": "Seller storefront | ₹999 | Live listing pack.\nDoUdyog + Bajar | ₹1,999 | Business identity plus shop.\nFeatured week | ₹499 | Homepage feature.",
    "seed": [
        ("Cold-pressed mustard oil 1L", "Grocery", "Jaipur", "₹280", "Local mill, weekly batch."),
        ("Handloom stole", "Textiles", "Varanasi", "₹650", "Cotton-silk, three colours."),
        ("MS angle 40x40", "Hardware", "Pune", "₹62 / kg", "Same-day pickup for fabricators."),
        ("Namkeen gift box", "Food", "Indore", "₹350", "Festival pack, 500g."),
    ],
}

PRODUCTS["doaaram"] = {
    "brand": "DoAaram",
    "mark": "A",
    "topbar": "DoAaram — Wellness, rest services and home care",
    "eyebrow": "आराम सही जगह",
    "hero_h1": "Book rest, wellness and home-care help.",
    "hero_p": "DoAaram is the ease planet of Do Galaxy. Providers list physiotherapy, elder care, massage and home rest services. Families send a date and a brief.",
    "footer_blurb": "Wellness and home-care services with a human desk.",
    "page_about": "DoAaram lists rest and care services. It is not a medical emergency line.",
    "join_cta": "List a service",
    "dir_label": "Services",
    "owner_role": "provider",
    "listing_label": "Service",
    "listing_table": "dg_services",
    "owner_col": "provider_id",
    "title_col": "title",
    "status_col": "status",
    "listing_mode": "many",
    "request_table": "dg_service_requests",
    "request_fk": "service_id",
    "request_label": "Booking",
    "fields": [
        ("title", "Service name", "text", True),
        ("category", "Category", "select", True, ["Physio", "Elder care", "Massage", "Yoga", "Home nurse", "Other"]),
        ("city", "City", "text", True),
        ("rate", "Rate", "text", False),
        ("about", "About", "textarea", False),
    ],
    "request_fields": [
        ("when_date", "Preferred date", "date", False),
        ("message", "What you need", "textarea", True),
    ],
    "docs": {"id": "Provider ID", "cert": "Certificate", "police": "Police verification if any"},
    "packages": "Provider listing | ₹999 | Verified care profile.\nHome visit pack | ₹1,999 | Four-visit schedule help.\nFamily care desk | ₹2,999 | Elder-care matching.",
    "seed": [
        ("Home physiotherapy", "Physio", "Pune", "₹800 / visit", "Post-surgery and back pain."),
        ("Day companion for elders", "Elder care", "Delhi", "₹1,200 / day", "Meals, walks, company."),
        ("Yoga at home", "Yoga", "Jaipur", "₹600 / session", "Beginner batches, women-only option."),
        ("Rest massage", "Massage", "Indore", "₹1,000", "Licensed therapist, home or studio."),
    ],
}

PRODUCTS["donirman"] = {
    "brand": "DoNirman",
    "mark": "N",
    "topbar": "DoNirman — Contractors and project leads",
    "eyebrow": "निर्माण भरोसे से",
    "hero_h1": "Find a contractor. Send the site brief.",
    "hero_p": "DoNirman is the build planet of Do Galaxy. Contractors list their trade. Homeowners and firms send project leads with city and scope.",
    "footer_blurb": "Construction trades and project leads, verified by staff.",
    "page_about": "DoNirman is a contractor directory and lead desk. It is not an escrow for construction payments.",
    "join_cta": "List your trade",
    "dir_label": "Contractors",
    "owner_role": "contractor",
    "listing_label": "Contractor",
    "listing_table": "dg_contractors",
    "owner_col": "owner_id",
    "title_col": "legal_name",
    "status_col": "verify_status",
    "listing_mode": "one",
    "request_table": "dg_project_leads",
    "request_fk": "contractor_id",
    "request_label": "Project lead",
    "fields": [
        ("legal_name", "Firm / name", "text", True),
        ("trade", "Trade", "select", True, ["Civil", "Electrical", "Plumbing", "Interiors", "Fabrication", "Painting"]),
        ("city", "City", "text", True),
        ("about", "About the work", "textarea", False),
    ],
    "request_fields": [
        ("site_city", "Site city", "text", False),
        ("message", "Scope", "textarea", True),
    ],
    "docs": {"gst": "GST / Udyam", "id": "ID", "work": "Past work photos"},
    "packages": "Contractor profile | ₹999 | Verified trade card.\nLead desk | ₹1,999 | Priority inbound leads.\nSite pack | ₹4,999 | Estimate checklist with advisor.",
    "seed": [
        ("Sharma Civil Works", "Civil", "Pune", "Foundations and grey structure."),
        ("Meena Electricals", "Electrical", "Jaipur", "House wiring and solar tie-in."),
        ("Khan Interiors", "Interiors", "Indore", "Modular kitchen and wardrobes."),
        ("Iyer Painting Co", "Painting", "Delhi", "Interior and weather coats."),
    ],
}

PRODUCTS["dovyapaar"] = {
    "brand": "DoVyapaar",
    "mark": "Y",
    "topbar": "DoVyapaar — Suppliers, trade and RFQs",
    "eyebrow": "व्यापार जुड़े",
    "hero_h1": "Find a supplier. Send an RFQ.",
    "hero_p": "DoVyapaar is the trade planet of Do Galaxy. Suppliers list category and city. Buyers send item, quantity and a message. Staff can verify suppliers.",
    "footer_blurb": "B2B discovery and request-for-quote for MSMEs.",
    "page_about": "DoVyapaar is a supplier directory and RFQ desk that sits next to DoUdyog identity.",
    "join_cta": "List as supplier",
    "dir_label": "Suppliers",
    "owner_role": "supplier",
    "listing_label": "Supplier",
    "listing_table": "dg_suppliers",
    "owner_col": "owner_id",
    "title_col": "legal_name",
    "status_col": "verify_status",
    "listing_mode": "one",
    "request_table": "dg_rfqs",
    "request_fk": "supplier_id",
    "request_label": "RFQ",
    "fields": [
        ("legal_name", "Firm name", "text", True),
        ("category", "Category", "select", True, ["Raw material", "Packaging", "Machinery", "Wholesale", "Logistics", "Other"]),
        ("city", "City", "text", True),
        ("about", "What you supply", "textarea", False),
    ],
    "request_fields": [
        ("item", "Item", "text", True),
        ("qty", "Quantity", "text", False),
        ("message", "Specs / note", "textarea", True),
    ],
    "docs": {"gst": "GSTIN", "udyam": "Udyam", "catalog": "Rate card"},
    "packages": "Supplier card | ₹999 | Verified trade listing.\nRFQ desk | ₹1,999 | Priority quotes.\nDoUdyog + Vyapaar | ₹2,499 | Identity plus trade.",
    "seed": [
        ("Aarambh Packaging", "Packaging", "Jaipur", "Corrugated boxes and tape."),
        ("Nirman Steels", "Raw material", "Indore", "TMT and structural steel."),
        ("Swagat Spices Wholesale", "Wholesale", "Delhi", "Bulk masala for kitchens."),
        ("Iyer Machine Tools", "Machinery", "Pune", "Lathe and milling spares."),
    ],
}

PRODUCTS["mydoapp"] = {
    "mode": "hub",
    "brand": "MyDoApp",
    "mark": "M",
    "topbar": "MyDoApp — Door to Do Galaxy",
    "eyebrow": "एक खाता, पूरा आकाश",
    "hero_h1": "One account. Every Do Galaxy planet.",
    "hero_p": "MyDoApp is the parent door. Register once, then run a business, find a stay, hire, host an event, match a family, or sell.",
    "footer_blurb": "Parent platform of Do Galaxy under Kusumit Universe.",
    "page_about": "MyDoApp is the hub. Each planet keeps its own operating desk. Your login is the shared identity.",
    "join_cta": "Create account",
    "dir_label": "Products",
    "owner_role": "member",
    "packages": "Galaxy start | Free | One account.\nVerified identity | ₹999 | Via DoUdyog.\nGuided onboarding | ₹2,999 | Staff walkthrough.",
    "paths": {
        "udyog": ["DoUdyog", "https://doudyog.com/app/", "I run a business"],
        "vishram": ["DoVishram", "https://dovishram.com/app/", "I need or host a stay"],
        "rojgar": ["DoRojgar", "https://dorojgar.com/app/", "I want work or to hire"],
        "swagat": ["DoSwagat", "https://doswagat.com/app/", "I am planning an event"],
        "rishta": ["DoRishta", "https://dorishta.com/app/", "I am looking for a life partner"],
        "bajar": ["DoBajar", "https://dobajar.com/app/", "I want to buy or sell"],
        "aaram": ["DoAaram", "https://doaaram.com/app/", "I need rest or care"],
        "nirman": ["DoNirman", "https://donirman.com/app/", "I have a build project"],
        "vyapaar": ["DoVyapaar", "https://dovyapaar.com/app/", "I buy or supply in trade"],
    },
}


def php_array(value, indent=0) -> str:
    sp = "    " * indent
    if isinstance(value, dict):
        inner = []
        for k, v in value.items():
            inner.append(f"{sp}    {php_val(k)} => {php_array(v, indent + 1) if isinstance(v, (dict, list, tuple)) else php_val(v)}")
        return "[\n" + ",\n".join(inner) + f",\n{sp}]"
    if isinstance(value, (list, tuple)):
        if value and isinstance(value[0], (list, tuple)) and not isinstance(value[0], str):
            parts = []
            for item in value:
                parts.append(sp + "    " + php_val_list(item))
            return "[\n" + ",\n".join(parts) + f",\n{sp}]"
        return "[" + ", ".join(php_val(x) if not isinstance(x, (list, tuple)) else php_val_list(x) for x in value) + "]"
    return php_val(value)


def php_val(v) -> str:
    if isinstance(v, bool):
        return "true" if v else "false"
    if isinstance(v, int):
        return str(v)
    if v is None:
        return "null"
    s = str(v).replace("\\", "\\\\").replace("'", "\\'")
    return f"'{s}'"


def php_val_list(item) -> str:
    return "[" + ", ".join(php_val(x) if not isinstance(x, (list, tuple)) else php_val_list(x) for x in item) + "]"


def write_product(slug: str, spec: dict) -> None:
    app = ROOT / "apps" / slug
    app.mkdir(parents=True, exist_ok=True)
    (app / "assets").mkdir(exist_ok=True)
    (app / "uploads").mkdir(exist_ok=True)
    spec = dict(spec)
    spec.setdefault("slug", slug)
    spec.setdefault("page_privacy", LEGAL["privacy"])
    spec.setdefault("page_terms", spec.get("page_terms", LEGAL["terms"]))
    spec.setdefault("contact_email", f"hello@{slug}.com")
    seed = spec.pop("seed", [])
    body = "<?php\nreturn " + php_array(spec) + ";\n"
    (app / "product.php").write_text(body, encoding="utf-8")
    (app / "index.php").write_text(INDEX, encoding="utf-8")
    (app / ".htaccess").write_text(HTACCESS, encoding="utf-8")
    (app / "uploads" / ".htaccess").write_text("Require all denied\n", encoding="utf-8")
    shutil.copyfile(CSS, app / "assets" / "app.css")
    install = ROOT / "apps" / "doudyog" / "install.php"
    shutil.copyfile(install, app / "install.php")
    (app / "seed.php").write_text(
        "<?php\nreturn " + php_array(seed) + ";\n",
        encoding="utf-8",
    )
    print("wrote", slug)


def main() -> None:
    for slug, spec in PRODUCTS.items():
        write_product(slug, spec)


if __name__ == "__main__":
    main()
