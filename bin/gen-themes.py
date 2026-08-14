#!/usr/bin/env python3
"""Generate WP child themes from products.json."""
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
products = json.loads((ROOT / "products.json").read_text())


def php_cpt(c):
    return f"""    register_post_type('{c['key']}', [
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


for slug, p in products.items():
    d = ROOT / "themes" / slug
    (d / "templates").mkdir(parents=True, exist_ok=True)
    name = p["name"]
    (d / "style.css").write_text(
        f"""/*
Theme Name: {name}
Template: dogalaxy-core
Theme URI: https://github.com/DrDeveshK/DoGalaxy
Author: DrDeveshK
Description: {p['tagline']}
Version: 0.1.0
Text Domain: {slug}
*/
"""
    )
    cpts = "\n".join(php_cpt(c) for c in p["cpt"])
    init = (
        f"""
add_action('init', function (): void {{
{cpts}
}});
"""
        if cpts
        else ""
    )
    (d / "functions.php").write_text(
        f"""<?php
if (!defined('ABSPATH')) {{
    exit;
}}

add_action('wp_enqueue_scripts', function (): void {{
    wp_enqueue_style(
        '{slug}',
        get_stylesheet_uri(),
        ['dogalaxy-core'],
        wp_get_theme()->get('Version')
    );
}});
{init}"""
    )
    (d / "templates" / "front-page.html").write_text(
        f"""<!-- wp:template-part {{"slug":"header","tagName":"header"}} /-->

<!-- wp:group {{"tagName":"main","layout":{{"type":"constrained"}}}} -->
<main class="wp-block-group">
  <!-- wp:heading {{"level":1}} -->
  <h1>{p['tagline']}</h1>
  <!-- /wp:heading -->
  <!-- wp:paragraph -->
  <p>Part of DoGalaxy · <a href="https://kusumit.com">Kusumit</a></p>
  <!-- /wp:paragraph -->
  <!-- wp:post-content /-->
</main>
<!-- /wp:group -->

<!-- wp:template-part {{"slug":"footer","tagName":"footer"}} /-->
"""
    )

print(f"generated {len(products)} child themes")
