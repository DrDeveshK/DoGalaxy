#!/usr/bin/env python3
"""Write elite child themes for remaining Do Galaxy products."""
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
THEMES = ROOT / "themes"

PRODUCTS = {
    "dovishram": {
        "name": "Do Vishram",
        "kicker": "विश्राम मिले, मन टिके",
        "h1": "Find peaceful stays, rooms, resorts and retreats.",
        "lede": "A trusted discovery platform for hotels, homestays, short stays and curated rest — for families, travellers and professionals.",
        "cta": "Enquire for a stay",
        "cta2": "Browse stays",
        "archive": "/stay/",
        "stats": [("420+", "Listed stays"), ("12", "Cities"), ("4", "Stay types"), ("6", "Do products")],
        "pillars": [
            ("01", "Stay discovery", "Hotels, homestays, resorts and rooms with a clear enquiry path."),
            ("02", "Rest packages", "Short breaks and wellness-led stays, not just a room night."),
            ("03", "Trust promise", "Verified hosts, transparent support, family-safe positioning."),
        ],
        "cross": [
            ("Events nearby", "Pair a stay with a celebration on Do Swagat.", "https://doswagat.com"),
            ("Hire local help", "Staff a property through Do Rojgar.", "https://dorojgar.com"),
            ("List your property", "Businesses start identity on Do Udyog.", "https://doudyog.com"),
        ],
        "cpts": [
            {"key": "stay", "plural": "Stays", "singular": "Stay", "slug": "stay", "icon": "dashicons-admin-home"}
        ],
        "seeds": [
            ("Ghat Homestay", "Ganga-facing rooms for families.", "Varanasi"),
            ("Aranya Retreat", "Weekend rest in a quiet grove.", "Jim Corbett"),
            ("Kashi Guest House", "Simple rooms near the old city.", "Varanasi"),
            ("Lakeview Rooms", "Short stay for travelling professionals.", "Udaipur"),
        ],
        "seed_tax": "city",
        "color": "#2f4a3c",
        "accent": "#6b8f71",
        "query": "stay",
        "dir_label": "Stays",
        "dir_kicker": "Rest, stays & wellness",
    },
    "dorojgar": {
        "name": "Do Rojgar",
        "kicker": "रोज़गार आपके पास",
        "h1": "Hyperlocal jobs. Real opportunities.",
        "lede": "Local jobs, skilled work, office roles and gigs — closer to seekers, simpler for employers.",
        "cta": "I am hiring",
        "cta2": "Find a job",
        "archive": "/jobs/",
        "stats": [("3,400+", "Job posts"), ("90+", "Towns"), ("2", "Journeys"), ("6", "Do products")],
        "pillars": [
            ("01", "Job search", "Roles near you — shop floor, office, gig and skilled work."),
            ("02", "Employer posting", "A simple channel for SMEs to find relevant people."),
            ("03", "Two dashboards", "Seeker and employer journeys, not one generic board."),
        ],
        "cross": [
            ("Hire for a business", "Verified firms start on Do Udyog.", "https://doudyog.com"),
            ("Event crews", "Staff weddings and functions via Do Swagat.", "https://doswagat.com"),
            ("Sell your skills", "Service businesses also list on Do Bajar.", "https://dobajar.com"),
        ],
        "cpts": [
            {"key": "job", "plural": "Jobs", "singular": "Job", "slug": "jobs", "icon": "dashicons-id"}
        ],
        "seeds": [
            ("Front desk associate", "Hospitality desk role, day shift.", "Jaipur"),
            ("Site supervisor", "Construction supervision for a local builder.", "Lucknow"),
            ("Accounts executive", "SME accounts and GST support.", "Delhi"),
            ("Delivery rider", "Hyperlocal gig, weekly payout.", "Kanpur"),
        ],
        "seed_tax": "city",
        "color": "#6b2d1a",
        "accent": "#c45c26",
        "query": "job",
        "dir_label": "Jobs",
        "dir_kicker": "Employment",
    },
    "doswagat": {
        "name": "Do Swagat",
        "kicker": "स्वागत हो",
        "h1": "Plan weddings, parties and corporate events with verified partners.",
        "lede": "Venues, caterers, decorators and photographers in one structured flow — for families and organisations.",
        "cta": "Start an enquiry",
        "cta2": "Browse venues",
        "archive": "/venues/",
        "stats": [("180+", "Venues"), ("340+", "Partners"), ("12", "Cities"), ("6", "Do products")],
        "pillars": [
            ("01", "Venues", "Halls, lawns and hotels that can actually host your date."),
            ("02", "Services", "Caterers, décor, photo and hospitality partners."),
            ("03", "Packages", "Curated event packages instead of a fragmented hunt."),
        ],
        "cross": [
            ("Stay for guests", "Rooms and retreats on Do Vishram.", "https://dovishram.com"),
            ("Hire crew", "Staff the event through Do Rojgar.", "https://dorojgar.com"),
            ("Vendor identity", "Partners verify on Do Udyog.", "https://doudyog.com"),
        ],
        "cpts": [
            {"key": "venue", "plural": "Venues", "singular": "Venue", "slug": "venues", "icon": "dashicons-location"},
            {"key": "event_service", "plural": "Services", "singular": "Service", "slug": "services", "icon": "dashicons-awards"},
            {"key": "event_package", "plural": "Packages", "singular": "Package", "slug": "packages", "icon": "dashicons-tickets-alt"},
        ],
        "seeds": [
            ("Riverfront Lawn", "Outdoor wedding lawn for 400 guests.", "Varanasi"),
            ("Mandap Hall", "Indoor banquet with in-house catering.", "Lucknow"),
            ("Courtyard House", "Intimate family functions.", "Jaipur"),
            ("Conference Pavilion", "Corporate days and offsites.", "Delhi"),
        ],
        "seed_tax": "city",
        "color": "#5c1a2e",
        "accent": "#b8954a",
        "query": "venue",
        "dir_label": "Venues",
        "dir_kicker": "Welcome, events & hospitality",
    },
    "dorishta": {
        "name": "Do Rishta",
        "kicker": "अपना रिश्ता यहीं",
        "h1": "A trusted, family-friendly way to find a life partner.",
        "lede": "Respectful matrimonial discovery — verification, relevance and room for families to participate.",
        "cta": "Begin with care",
        "cta2": "Read stories",
        "archive": "/stories/",
        "stats": [("Verified", "Profile promise"), ("Family", "First design"), ("Guided", "Matching"), ("6", "Do products")],
        "pillars": [
            ("01", "Verified profiles", "Trust before chat. Identity and intent come first."),
            ("02", "Compatibility", "Relevance over volume — fewer, better introductions."),
            ("03", "Family room", "Parents and elders can walk the journey with you."),
        ],
        "cross": [
            ("Celebrate together", "Weddings planned on Do Swagat.", "https://doswagat.com"),
            ("Guest stays", "Families travel with Do Vishram.", "https://dovishram.com"),
            ("Work and home", "Careers via Do Rojgar.", "https://dorojgar.com"),
        ],
        "cpts": [
            {"key": "success_story", "plural": "Success Stories", "singular": "Story", "slug": "stories", "icon": "dashicons-heart"}
        ],
        "seeds": [
            ("Asha & Rohan", "A family-led introduction that took its time.", "Lucknow"),
            ("Meera & Kabir", "Two professionals, one quiet yes.", "Pune"),
            ("Nandini & Arjun", "Community depth, modern consent.", "Jaipur"),
            ("Zara & Vikram", "Guided matching, not a swipe.", "Delhi"),
        ],
        "seed_tax": "city",
        "color": "#4a2040",
        "accent": "#a85a7a",
        "query": "success_story",
        "dir_label": "Stories",
        "dir_kicker": "Relationships & matrimony",
    },
    "dobajar": {
        "name": "Do Bajar",
        "kicker": "बाज़ार आपके द्वार",
        "h1": "A marketplace where local sellers become visible.",
        "lede": "The commerce layer of Do Galaxy. Businesses from Do Udyog get storefronts; buyers discover products in a structured bazaar.",
        "cta": "Become a seller",
        "cta2": "Browse listings",
        "archive": "/listings/",
        "stats": [("Storefronts", "Not just ads"), ("Local", "First"), ("Udyog", "Linked"), ("6", "Do products")],
        "pillars": [
            ("01", "Seller visibility", "A digital stall for shops and makers who are already real."),
            ("02", "Product discovery", "Structured listings instead of a noisy feed."),
            ("03", "From identity to sale", "Do Udyog verifies; Do Bajar sells."),
        ],
        "cross": [
            ("Verify first", "Business identity on Do Udyog.", "https://doudyog.com"),
            ("Need staff", "Hire through Do Rojgar.", "https://dorojgar.com"),
            ("Need stock", "Trade roots in the wider galaxy.", "https://doudyog.com"),
        ],
        "cpts": [
            {"key": "listing", "plural": "Listings", "singular": "Listing", "slug": "listings", "icon": "dashicons-cart"}
        ],
        "seeds": [
            ("Handloom stoles", "Weaver collective, limited runs.", "Varanasi"),
            ("Spice tins", "Kitchen staples from a family mill.", "Kanpur"),
            ("Brass diyas", "Festival sets, wholesale and retail.", "Moradabad"),
            ("School notebooks", "Local printer, bulk orders.", "Lucknow"),
        ],
        "seed_tax": "city",
        "color": "#3d2a12",
        "accent": "#c4a035",
        "query": "listing",
        "dir_label": "Listings",
        "dir_kicker": "Marketplace & commerce",
    },
    "mydoapp": {
        "name": "MyDoApp",
        "kicker": "Do Galaxy",
        "h1": "Trusted digital services, one universe.",
        "lede": "Six focused products that work alone — and become more valuable when a person or business moves across them.",
        "cta": "Enter Do Udyog",
        "cta2": "See the six",
        "archive": "https://doudyog.com",
        "stats": [("6", "Products"), ("1", "Trust layer"), ("India", "First"), ("Connected", "Journeys")],
        "pillars": [
            ("01", "Independent value", "Each product grows in its own sector and partnership network."),
            ("02", "Shared trust", "Verification and partner quality as a common layer."),
            ("03", "Cross journeys", "A business hires, hosts and sells without leaving the universe."),
        ],
        "cross": [
            ("Do Udyog", "MSME identity and growth.", "https://doudyog.com"),
            ("Do Vishram", "Stays and rest.", "https://dovishram.com"),
            ("Do Rojgar", "Hyperlocal jobs.", "https://dorojgar.com"),
            ("Do Swagat", "Events and hospitality.", "https://doswagat.com"),
            ("Do Rishta", "Family-friendly matrimony.", "https://dorishta.com"),
            ("Do Bajar", "Marketplace and commerce.", "https://dobajar.com"),
        ],
        "cpts": [],
        "seeds": [],
        "seed_tax": "city",
        "color": "#1a2744",
        "accent": "#b8954a",
        "query": "",
        "dir_label": "",
        "dir_kicker": "Parent platform",
    },
}


def php_cpts(cpts):
    blocks = []
    for c in cpts:
        blocks.append(
            f"""    register_post_type('{c['key']}', [
        'labels' => [
            'name' => '{c['plural']}',
            'singular_name' => '{c['singular']}',
        ],
        'public' => true,
        'has_archive' => true,
        'menu_icon' => '{c['icon']}',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        'rewrite' => ['slug' => '{c['slug']}'],
        'show_in_rest' => true,
    ]);"""
        )
    tax = """    register_taxonomy('city', ['%s'], [
        'label' => 'Cities',
        'public' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'city'],
    ]);""" % "', '".join(c["key"] for c in cpts) if cpts else ""
    return "\n".join(blocks + ([tax] if tax else []))


def php_seeds(slug, p):
    if not p["seeds"] or not p["cpts"]:
        return ""
    key = p["cpts"][0]["key"]
    rows = ",\n        ".join(
        f"['{t}', '{e}', '{c}']" for t, e, c in p["seeds"]
    )
    return f"""
add_action('after_switch_theme', function (): void {{
    if (get_option('{slug}_seeded')) {{
        return;
    }}
    $samples = [
        {rows}
    ];
    foreach ($samples as [$title, $excerpt, $city]) {{
        $id = wp_insert_post([
            'post_type' => '{key}',
            'post_status' => 'publish',
            'post_title' => $title,
            'post_excerpt' => $excerpt,
            'post_content' => $excerpt,
        ]);
        if (!is_wp_error($id)) {{
            wp_set_object_terms($id, $city, 'city');
        }}
    }}
    update_option('{slug}_seeded', 1);
}});
"""


def card(title, body, href=None):
    extra = f' <a href="{href}">Open</a>' if href else ""
    return f"""      <!-- wp:column -->
      <div class="wp-block-column">
        <!-- wp:group {{"className":"dg-card"}} -->
        <div class="wp-block-group dg-card">
          <!-- wp:heading {{"level":3}} --><h3>{title}</h3><!-- /wp:heading -->
          <!-- wp:paragraph --><p>{body}{extra}</p><!-- /wp:paragraph -->
        </div>
        <!-- /wp:group -->
      </div>
      <!-- /wp:column -->"""


def front(slug, p):
    stats = "\n".join(
        f"""    <!-- wp:column -->
    <div class="wp-block-column"><!-- wp:heading {{"level":3}} --><h3>{n}</h3><!-- /wp:heading --><!-- wp:paragraph --><p>{l}</p><!-- /wp:paragraph --></div>
    <!-- /wp:column -->"""
        for n, l in p["stats"]
    )
    pillars = "\n".join(
        f"""    <!-- wp:column -->
    <div class="wp-block-column">
      <!-- wp:group {{"className":"dg-card"}} -->
      <div class="wp-block-group dg-card">
        <!-- wp:paragraph {{"className":"dg-mark"}} --><p class="dg-mark">{n}</p><!-- /wp:paragraph -->
        <!-- wp:heading {{"level":3}} --><h3>{t}</h3><!-- /wp:heading -->
        <!-- wp:paragraph --><p>{b}</p><!-- /wp:paragraph -->
      </div>
      <!-- /wp:group -->
    </div>
    <!-- /wp:column -->"""
        for n, t, b in p["pillars"]
    )
    cross = "\n".join(card(t, b, h) for t, b, h in p["cross"])
    query = ""
    if p["query"]:
        query = f"""
<!-- wp:group {{"className":"dg-band","layout":{{"type":"constrained"}}}} -->
<div class="wp-block-group dg-band">
  <!-- wp:heading {{"level":2}} --><h2>Featured {p['dir_label'].lower()}</h2><!-- /wp:heading -->
  <!-- wp:query {{"query":{{"perPage":6,"postType":"{p['query']}","inherit":false}}}} -->
  <div class="wp-block-query">
    <!-- wp:post-template {{"layout":{{"type":"grid","columnCount":2}}}} -->
      <!-- wp:group {{"className":"dg-card"}} -->
      <div class="wp-block-group dg-card">
        <!-- wp:post-title {{"isLink":true,"level":3}} /-->
        <!-- wp:post-excerpt /-->
      </div>
      <!-- /wp:group -->
    <!-- /wp:post-template -->
  </div>
  <!-- /wp:query -->
  <!-- wp:paragraph --><p><a href="{p['archive']}">See all {p['dir_label'].lower()}</a></p><!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
"""
    cta_href = "#enquire" if slug != "mydoapp" else "https://doudyog.com"
    cta2_href = p["archive"]
    return f"""<!-- wp:template-part {{"slug":"header","tagName":"header"}} /-->

<!-- wp:group {{"tagName":"main"}} -->
<main class="wp-block-group">

<!-- wp:group {{"className":"dg-hero dg-{slug}-hero","layout":{{"type":"constrained"}}}} -->
<div class="wp-block-group dg-hero dg-{slug}-hero">
  <!-- wp:paragraph {{"className":"dg-kicker"}} --><p class="dg-kicker">{p['kicker']}</p><!-- /wp:paragraph -->
  <!-- wp:heading {{"level":1}} --><h1>{p['h1']}</h1><!-- /wp:heading -->
  <!-- wp:paragraph {{"className":"dg-lede"}} --><p class="dg-lede">{p['lede']}</p><!-- /wp:paragraph -->
  <!-- wp:buttons {{"className":"dg-actions"}} -->
  <div class="wp-block-buttons dg-actions">
    <!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="{cta_href}">{p['cta']}</a></div><!-- /wp:button -->
    <!-- wp:button {{"className":"dg-btn-ghost"}} --><div class="wp-block-button dg-btn-ghost"><a class="wp-block-button__link wp-element-button" href="{cta2_href}">{p['cta2']}</a></div><!-- /wp:button -->
  </div>
  <!-- /wp:buttons -->
  <!-- wp:columns {{"className":"dg-stats"}} -->
  <div class="wp-block-columns dg-stats">
{stats}
  </div>
  <!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:group {{"className":"dg-band dg-band-alt alignfull","layout":{{"type":"constrained"}}}} -->
<div class="wp-block-group dg-band dg-band-alt alignfull">
  <!-- wp:heading {{"level":2}} --><h2>How {p['name']} works</h2><!-- /wp:heading -->
  <!-- wp:columns -->
  <div class="wp-block-columns">
{pillars}
  </div>
  <!-- /wp:columns -->
</div>
<!-- /wp:group -->
{query}
<!-- wp:group {{"className":"dg-band","layout":{{"type":"constrained"}}}} -->
<div class="wp-block-group dg-band">
  <!-- wp:heading {{"level":2}} --><h2>Connected across Do Galaxy</h2><!-- /wp:heading -->
  <!-- wp:columns -->
  <div class="wp-block-columns">
{cross}
  </div>
  <!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:group {{"anchor":"enquire","className":"dg-band dg-band-alt alignfull","layout":{{"type":"constrained"}}}} -->
<div class="wp-block-group dg-band dg-band-alt alignfull" id="enquire">
  <!-- wp:heading {{"level":2}} --><h2>Talk to {p['name']}</h2><!-- /wp:heading -->
  <!-- wp:shortcode -->
  [do_enquiry]
  <!-- /wp:shortcode -->
</div>
<!-- /wp:group -->

</main>
<!-- /wp:group -->

<!-- wp:template-part {{"slug":"footer","tagName":"footer"}} /-->
"""


def archive_tpl(p):
    if not p["query"]:
        return None
    return f"""<!-- wp:template-part {{"slug":"header","tagName":"header"}} /-->
<!-- wp:group {{"tagName":"main","className":"dg-band","layout":{{"type":"constrained"}}}} -->
<main class="wp-block-group dg-band">
  <!-- wp:paragraph {{"className":"dg-kicker"}} --><p class="dg-kicker">{p['dir_kicker']}</p><!-- /wp:paragraph -->
  <!-- wp:heading {{"level":1}} --><h1>{p['dir_label']}</h1><!-- /wp:heading -->
  <!-- wp:query {{"query":{{"perPage":12,"inherit":true}}}} -->
  <div class="wp-block-query">
    <!-- wp:post-template {{"layout":{{"type":"grid","columnCount":2}}}} -->
      <!-- wp:group {{"className":"dg-card"}} -->
      <div class="wp-block-group dg-card">
        <!-- wp:post-title {{"isLink":true,"level":3}} /-->
        <!-- wp:post-excerpt /-->
      </div>
      <!-- /wp:group -->
    <!-- /wp:post-template -->
    <!-- wp:query-pagination /-->
  </div>
  <!-- /wp:query -->
</main>
<!-- /wp:group -->
<!-- wp:template-part {{"slug":"footer","tagName":"footer"}} /-->
"""


def single_tpl(p):
    if not p["query"]:
        return None
    return f"""<!-- wp:template-part {{"slug":"header","tagName":"header"}} /-->
<!-- wp:group {{"tagName":"main","className":"dg-band","layout":{{"type":"constrained"}}}} -->
<main class="wp-block-group dg-band">
  <!-- wp:paragraph {{"className":"dg-kicker"}} --><p class="dg-kicker">{p['dir_kicker']}</p><!-- /wp:paragraph -->
  <!-- wp:post-title {{"level":1}} /-->
  <!-- wp:post-excerpt /-->
  <!-- wp:post-content /-->
  <!-- wp:paragraph --><p><a href="{p['archive']}">← All {p['dir_label'].lower()}</a> · <a href="/#enquire">Enquire</a></p><!-- /wp:paragraph -->
</main>
<!-- /wp:group -->
<!-- wp:template-part {{"slug":"footer","tagName":"footer"}} /-->
"""


def write_product(slug, p):
    d = THEMES / slug
    (d / "templates").mkdir(parents=True, exist_ok=True)
    (d / "style.css").write_text(
        f"""/*
Theme Name: {p['name']}
Template: dogalaxy-core
Theme URI: https://github.com/DrDeveshK/DoGalaxy
Author: Dr. Devesh Kumar Sharma
Description: {p['lede']}
Version: 1.0.0
Text Domain: {slug}
*/

.dg-{slug}-hero {{
  background:
    radial-gradient(900px 420px at 90% -10%, {p['accent']}33, transparent 60%),
    var(--wp--preset--color--paper);
}}
"""
    )
    (d / "theme.json").write_text(
        f"""{{
  "$schema": "https://schemas.wp.org/trunk/theme.json",
  "version": 3,
  "settings": {{
    "color": {{
      "palette": [
        {{ "slug": "ink", "color": "#14110e", "name": "Ink" }},
        {{ "slug": "ink-soft", "color": "#3d3832", "name": "Ink soft" }},
        {{ "slug": "paper", "color": "#f4efe6", "name": "Paper" }},
        {{ "slug": "paper-2", "color": "#ebe4d6", "name": "Paper 2" }},
        {{ "slug": "white", "color": "#fffcf7", "name": "White" }},
        {{ "slug": "forest", "color": "{p['color']}", "name": "Brand" }},
        {{ "slug": "saffron", "color": "{p['accent']}", "name": "Accent" }},
        {{ "slug": "gold", "color": "#b8954a", "name": "Gold" }},
        {{ "slug": "line", "color": "#d9d0c0", "name": "Line" }}
      ]
    }}
  }}
}}
"""
    )
    init = php_cpts(p["cpts"])
    init_php = (
        f"\nadd_action('init', function (): void {{\n{init}\n}});\n" if init else ""
    )
    (d / "functions.php").write_text(
        f"""<?php
if (!defined('ABSPATH')) {{
    exit;
}}

add_action('wp_enqueue_scripts', function (): void {{
    wp_enqueue_style('{slug}', get_stylesheet_uri(), ['dogalaxy-core'], wp_get_theme()->get('Version'));
}});
{init_php}{php_seeds(slug, p)}
"""
    )
    (d / "templates" / "front-page.html").write_text(front(slug, p))
    arch = archive_tpl(p)
    if arch and p["cpts"]:
        (d / "templates" / f"archive-{p['cpts'][0]['key']}.html").write_text(arch)
        (d / "templates" / f"single-{p['cpts'][0]['key']}.html").write_text(single_tpl(p))
    print("wrote", slug)


def main():
    for slug, p in PRODUCTS.items():
        write_product(slug, p)


if __name__ == "__main__":
    main()
