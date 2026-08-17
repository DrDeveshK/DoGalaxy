<?php
declare(strict_types=1);

function product_migrate(PDO $pdo): void
{
}

function product_seed(PDO $db): void
{
}

function product_handle_post(PDO $db, string $act, string &$err): bool
{
    return false;
}

function product_render_page(PDO $db, string $page, array $P, ?array $me): bool
{
    if ($page !== 'router') {
        return false;
    }
    $q = trim((string) ($_GET['q'] ?? ''));
    $ans = $q !== '' ? galaxy_answer($q, 'mydoapp') : null;
    echo '<section class="section soft"><div class="container"><div class="section-title"><div><h2>Find my Do planet</h2><p>Tell MyDoApp what you need. It routes you to the right desk.</p></div><a class="btn light" href="?p=guide">Ask Do</a></div><div class="card" style="max-width:760px">';
    echo '<form method="get"><input type="hidden" name="p" value="router"><div class="form-row"><input class="input" name="q" value="' . h($q) . '" placeholder="Need plumber, job, stay, wedding venue, RFQ..."><button class="btn" type="submit">Find</button></div></form>';
    if ($ans) {
        echo '<hr><h3>' . h(galaxy_planets()[$ans['planet']]['brand'] ?? 'MyDoApp') . '</h3><p>' . h($ans['answer']) . '</p><div class="meta">';
        foreach ($ans['links'] as $l) {
            echo '<a class="pill" href="' . h($l['url']) . '">' . h($l['label']) . '</a>';
        }
        echo '</div>';
    }
    echo '</div></div></section>';
    return true;
}
