<?php
declare(strict_types=1);

require_once __DIR__ . '/galaxy.php';

function guide_answer(PDO $db, string $q, string $here): array
{
    $ans = galaxy_answer($q, $here);
    if (($ans['confidence'] ?? 0) > 0) {
        return $ans;
    }
    $llm = guide_llm_answer($db, $q, $here);
    return $llm ?: $ans;
}

function guide_llm_answer(PDO $db, string $q, string $here): ?array
{
    if (!function_exists('setting') || !function_exists('curl_init')) {
        return null;
    }
    $url = trim(setting('guide_llm_url'));
    $key = trim(setting('guide_llm_key'));
    if ($url === '' || $key === '' || trim($q) === '') {
        return null;
    }
    $ctx = [];
    foreach (galaxy_planets() as $slug => $p) {
        $ctx[] = $p['brand'] . ' (' . $slug . '): ' . $p['intent'] . ' Safety: ' . $p['safety'];
    }
    $payload = json_encode([
        'model' => trim(setting('guide_llm_model', 'gpt-4o-mini')),
        'messages' => [
            ['role' => 'system', 'content' => "You are Ask Do, a concise Do Galaxy router. Use only this ecosystem map:\n" . implode("\n", $ctx)],
            ['role' => 'user', 'content' => $q],
        ],
        'temperature' => 0.2,
    ]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $key],
        CURLOPT_TIMEOUT => 6,
    ]);
    $raw = (string) curl_exec($ch);
    curl_close($ch);
    $data = json_decode($raw, true);
    $text = trim((string) ($data['choices'][0]['message']['content'] ?? ''));
    if ($text === '') {
        return null;
    }
    return ['answer' => $text, 'links' => [['label' => 'Open MyDoApp', 'url' => galaxy_url('mydoapp')]], 'planet' => galaxy_slug($here), 'confidence' => 1];
}

function guide_json_response(PDO $db, string $here): never
{
    $body = json_decode((string) file_get_contents('php://input'), true);
    $q = is_array($body) ? (string) ($body['q'] ?? '') : (string) ($_POST['q'] ?? '');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(guide_answer($db, $q, $here), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function guide_render_page(PDO $db, string $here): void
{
    $q = trim((string) ($_GET['q'] ?? ''));
    $ans = $q === '' ? null : guide_answer($db, $q, $here);
    echo '<section class="section soft"><div class="container"><div class="section-title"><div><h2>Ask Do</h2><p>Free Do Galaxy knowledge desk. It knows MyDoApp and every planet without a paid AI API.</p></div></div>';
    echo '<div class="card" style="max-width:760px"><form method="get"><input type="hidden" name="p" value="guide"><label>Ask about any Do service</label><div class="form-row"><input class="input" name="q" value="' . h($q) . '" placeholder="home care, cost estimate, jobs, RFQ, 21+ safety"><button class="btn" type="submit">Ask</button></div></form>';
    if ($ans) {
        echo '<hr><h3>Answer</h3><p>' . h($ans['answer']) . '</p><div class="meta">';
        foreach ($ans['links'] as $l) {
            echo '<a class="pill" href="' . h($l['url']) . '">' . h($l['label']) . '</a>';
        }
        echo '</div>';
    }
    echo '</div></div></section>';
}

function guide_render_galaxy_strip(string $here): void
{
    echo '<section class="section soft"><div class="container"><div class="section-title"><div><h2>Other Do planets</h2><p>Move across the ecosystem with local-aware links.</p></div><a class="btn light" href="?p=guide">Ask Do</a></div><div class="grid-3">';
    foreach (galaxy_planets() as $slug => $p) {
        if ($slug === galaxy_slug($here)) {
            continue;
        }
        echo '<div class="feature"><h3>' . h($p['brand']) . '</h3><p>' . h($p['intent']) . '</p><p><a class="btn light" href="' . h(galaxy_url($slug)) . '">Open</a></p></div>';
    }
    echo '</div></div></section>';
}

function guide_render_footer_links(): void
{
    foreach (galaxy_planets() as $slug => $p) {
        echo '<a href="' . h(galaxy_url($slug)) . '">' . h($p['brand']) . '</a>';
    }
}

function guide_widget(string $here): void
{
    echo '<style>.ask-do{position:fixed;right:18px;bottom:18px;z-index:120}.ask-do button{box-shadow:0 14px 34px rgba(20,30,50,.22)}.ask-do-panel{display:none;width:min(360px,calc(100vw - 36px));margin-bottom:10px;background:#fff;color:#1f2937;border:1px solid #d9e1ef;border-radius:18px;box-shadow:0 22px 60px rgba(20,30,50,.25);overflow:hidden}.ask-do.open .ask-do-panel{display:block}.ask-do-head{padding:14px 16px;background:linear-gradient(135deg,var(--navy),var(--blue));color:#fff;font-weight:900}.ask-do-body{padding:14px}.ask-do-answer{font-size:14px;color:#374151;margin-top:10px}.ask-do-links a{display:inline-block;margin:6px 6px 0 0;font-size:12px;background:var(--soft);border:1px solid var(--border);border-radius:999px;padding:5px 9px}.skip-link{position:absolute;left:-999px;top:auto}.skip-link:focus{left:12px;top:12px;z-index:200;background:#fff;color:#111;padding:10px;border-radius:8px}</style>';
    echo '<div class="ask-do" id="askDo"><div class="ask-do-panel"><div class="ask-do-head">Ask Do</div><div class="ask-do-body"><form id="askDoForm"><input class="input" name="q" placeholder="Ask about any Do planet"><br><br><button class="btn" type="submit">Ask</button> <a href="?p=guide">Full page</a></form><div class="ask-do-answer" id="askDoAnswer">Try: home care, cost estimate, jobs, RFQ, 21+ safety.</div></div></div><button class="btn" type="button" onclick="document.getElementById(\'askDo\').classList.toggle(\'open\')">Ask Do</button></div>';
    echo '<script>document.getElementById("askDoForm")?.addEventListener("submit",async function(e){e.preventDefault();const box=document.getElementById("askDoAnswer");box.textContent="Thinking...";const fd=new FormData(this);try{const r=await fetch("?p=guide",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({q:fd.get("q")||"",here:"' . h($here) . '"})});const j=await r.json();box.innerHTML="<p>"+String(j.answer||"").replace(/[&<>]/g,s=>({"&":"&amp;","<":"&lt;",">":"&gt;"}[s]))+"</p><div class=\\"ask-do-links\\">"+(j.links||[]).map(l=>"<a href=\\""+l.url+"\\">"+l.label+"</a>").join("")+"</div>"}catch(_){box.textContent="Ask Do is available from the full page."}});</script>';
}
