<?php
declare(strict_types=1);

function galaxy_slug(string $slug): string
{
    $slug = strtolower(preg_replace('/[^a-z]/', '', $slug));
    return [
        'udyog' => 'doudyog',
        'vishram' => 'dovishram',
        'rojgar' => 'dorojgar',
        'swagat' => 'doswagat',
        'rishta' => 'dorishta',
        'bajar' => 'dobajar',
        'aaram' => 'doaaram',
        'nirman' => 'donirman',
        'vyapaar' => 'dovyapaar',
    ][$slug] ?? $slug;
}

function galaxy_planets(): array
{
    return [
        'mydoapp' => [
            'brand' => 'MyDoApp',
            'intent' => 'One account and the front door to every Do Galaxy planet.',
            'live' => 'https://mydoapp.com/app/',
            'port' => 8080,
            'pages' => ['products' => 'Choose a planet', 'join' => 'Create account', 'guide' => 'Ask Do'],
            'keywords' => ['mydoapp', 'hub', 'account', 'login', 'galaxy', 'ecosystem', 'planet', 'start'],
            'safety' => 'Use MyDoApp when you are not sure which Do planet fits your need.',
        ],
        'doudyog' => [
            'brand' => 'DoUdyog',
            'intent' => 'MSME business identity, compliance, directory, verification and growth.',
            'live' => 'https://doudyog.com/app/',
            'port' => 8081,
            'pages' => ['dir' => 'Business directory', 'services' => 'Business services', 'growth' => 'Growth programs', 'pricing' => 'Packages'],
            'keywords' => ['business', 'msme', 'udyog', 'gst', 'gstin', 'udyam', 'compliance', 'company', 'firm', 'verification'],
            'safety' => 'DoUdyog is an operating desk, not government or legal advice.',
        ],
        'dovishram' => [
            'brand' => 'DoVishram',
            'intent' => 'Stays, rooms, retreats and rest packages.',
            'live' => 'https://dovishram.com/app/',
            'port' => 8082,
            'pages' => ['dir' => 'Stay directory', 'packages' => 'Rest packages', 'track' => 'Track booking'],
            'keywords' => ['stay', 'hotel', 'room', 'resort', 'retreat', 'rest', 'vishram', 'booking'],
            'safety' => 'Confirm dates, guests and property rules before travel.',
        ],
        'dorojgar' => [
            'brand' => 'DoRojgar',
            'intent' => 'Hyperlocal jobs, hiring, applications, resume builder and career center.',
            'live' => 'https://dorojgar.com/app/',
            'port' => 8083,
            'pages' => ['dir' => 'Jobs', 'resume' => 'Resume builder', 'career' => 'Career center'],
            'keywords' => ['job', 'jobs', 'hiring', 'hire', 'resume', 'cv', 'career', 'work', 'rojgar', 'candidate'],
            'safety' => 'Never pay a recruiter before verification.',
        ],
        'doswagat' => [
            'brand' => 'DoSwagat',
            'intent' => 'Hospitality, venues, event packages, partner desk and request tracking.',
            'live' => 'https://doswagat.com/app/',
            'port' => 8084,
            'pages' => ['dir' => 'Venues', 'packages' => 'Event packages', 'track' => 'Track request', 'partner' => 'Partner desk'],
            'keywords' => ['event', 'wedding', 'venue', 'hospitality', 'swagat', 'package', 'partner', 'catering'],
            'safety' => 'Share date, city, guest count and budget for a useful event quote.',
        ],
        'dorishta' => [
            'brand' => 'DoRishta',
            'intent' => 'Family matrimony profiles, matches, membership and safety.',
            'live' => 'https://dorishta.com/app/',
            'port' => 8085,
            'pages' => ['matches' => 'Find matches', 'join' => 'Complete profile', 'membership' => 'Membership', 'safety' => 'Safety promise'],
            'keywords' => ['rishta', 'matrimony', 'marriage', 'match', 'profile', 'family', '21', 'safety', 'membership'],
            'safety' => 'DoRishta is 21+ and family-first. It is not a dating app.',
        ],
        'dobajar' => [
            'brand' => 'DoBajar',
            'intent' => 'Hyperlocal retail, kirana, daily essentials, neighbourhood shops, local pickup and delivery.',
            'live' => 'https://dobajar.com/app/',
            'port' => 8086,
            'pages' => ['shop' => 'Browse neighbourhood shops', 'track' => 'Track order', 'dir' => 'Products'],
            'keywords' => ['shop', 'buy', 'sell', 'product', 'order', 'track', 'bajar', 'market', 'store', 'kirana', 'retail', 'local', 'nearby', 'neighbourhood', 'neighborhood', 'delivery', 'essentials'],
            'safety' => 'Track orders from the same email and reference number used at request time.',
        ],
        'doaaram' => [
            'brand' => 'DoAaram',
            'intent' => 'UrbanCompany-style multi-service home help: handyman repairs, cleaning, pest control, wellness and care.',
            'live' => 'https://doaaram.com/app/',
            'port' => 8087,
            'pages' => ['categories' => 'Service categories', 'packs' => 'Service packs', 'care' => 'Family care desk', 'track' => 'Track booking', 'dir' => 'Pros'],
            'keywords' => ['care', 'home care', 'elder', 'physio', 'nurse', 'wellness', 'aaram', 'visit', 'family care', 'handyman', 'plumber', 'plumbing', 'electrician', 'electrical', 'carpenter', 'cleaning', 'pest', 'appliance repair', 'urban company', 'urbanclap'],
            'safety' => 'DoAaram is not a medical emergency line. It routes household service requests to listed providers.',
        ],
        'donirman' => [
            'brand' => 'DoNirman',
            'intent' => 'Construction contractors, project cost estimator, materials and NirmanGyan.',
            'live' => 'https://donirman.com/app/',
            'port' => 8088,
            'pages' => ['estimate' => 'Cost estimator', 'materials' => 'Materials bazaar', 'gyan' => 'NirmanGyan', 'dir' => 'Contractors'],
            'keywords' => ['construction', 'contractor', 'build', 'estimate', 'cost', 'material', 'cement', 'nirman', 'house', 'project'],
            'safety' => 'Estimates are planning numbers; final rates depend on site, soil, drawings and contractor scope.',
        ],
        'dovyapaar' => [
            'brand' => 'DoVyapaar',
            'intent' => 'B2B trade, suppliers, RFQ board, products and buy/sell leads.',
            'live' => 'https://dovyapaar.com/app/',
            'port' => 8089,
            'pages' => ['requirement' => 'Post requirement', 'board' => 'RFQ board', 'products' => 'Trade products', 'leads' => 'Buy/sell leads'],
            'keywords' => ['b2b', 'trade', 'supplier', 'rfq', 'quote', 'lead', 'buy lead', 'sell lead', 'vyapaar', 'wholesale'],
            'safety' => 'Verify supplier identity and payment terms before dispatch.',
        ],
    ];
}

function galaxy_url(string $slug, string $page = ''): string
{
    $slug = galaxy_slug($slug);
    $p = galaxy_planets()[$slug] ?? galaxy_planets()['mydoapp'];
    $base = (function_exists('is_local') && is_local()) ? 'http://127.0.0.1:' . $p['port'] . '/' : $p['live'];
    return $page === '' ? $base : $base . '?p=' . rawurlencode($page);
}

function galaxy_answer(string $q, string $here = ''): array
{
    $q = trim($q);
    $text = strtolower($q);
    $planets = galaxy_planets();
    $scores = [];
    foreach ($planets as $slug => $p) {
        $score = $slug === galaxy_slug($here) ? 1 : 0;
        foreach ($p['keywords'] as $kw) {
            if ($kw !== '' && str_contains($text, strtolower($kw))) {
                $score += str_contains($kw, ' ') ? 4 : 3;
            }
        }
        foreach ($p['pages'] as $page => $label) {
            if (str_contains($text, strtolower($page)) || str_contains($text, strtolower($label))) {
                $score += 2;
            }
        }
        $scores[$slug] = $score;
    }
    arsort($scores);
    $best = (string) array_key_first($scores);
    $confidence = (int) ($scores[$best] ?? 0);
    if ($q === '') {
        $best = galaxy_slug($here) ?: 'mydoapp';
        $confidence = 0;
    }
    $p = $planets[$best] ?? $planets['mydoapp'];
    $links = [];
    foreach ($p['pages'] as $page => $label) {
        $links[] = ['label' => $label, 'url' => galaxy_url($best, $page)];
    }
    if ($confidence <= 1) {
        return [
            'answer' => 'I can route you across Do Galaxy. Ask about jobs, business compliance, home care, stays, events, matrimony, shopping, construction cost, or B2B trade.',
            'links' => [
                ['label' => 'Open MyDoApp products', 'url' => galaxy_url('mydoapp', 'products')],
                ['label' => 'Ask from the hub', 'url' => galaxy_url('mydoapp', 'guide')],
            ],
            'planet' => 'mydoapp',
            'confidence' => 0,
        ];
    }
    return [
        'answer' => $p['brand'] . ': ' . $p['intent'] . ' ' . $p['safety'],
        'links' => $links,
        'planet' => $best,
        'confidence' => $confidence,
    ];
}
