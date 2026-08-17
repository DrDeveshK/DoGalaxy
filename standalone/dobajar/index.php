<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>DoBajar</title>
<style>/*
Theme Name: DoBajar Working MVP
Theme URI: https://dobajar.com
Author: Kusumit Pvt Ltd
Description: PHP 7.4 compatible WordPress MVP for DoUdyog - business identity, MSME services, compliance and growth under Do Galaxy.
Version: 1.0.0
Requires at least: 5.8
Requires PHP: 7.4
Text Domain: dobajar
*/
:root{--navy:#071f3d;--blue:#0b4f9c;--orange:#ff8a00;--soft:#f4f7fb;--text:#122033;--muted:#627084;--green:#14a46c;--border:#dde6f2;--white:#fff}
*{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;color:var(--text);line-height:1.55}a{text-decoration:none;color:inherit}.container{max-width:1180px;margin:auto;padding:0 20px}.topbar{background:var(--navy);color:#dbe8ff;font-size:13px;padding:8px 0}.topbar .container,.header-inner{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}.site-header{position:sticky;top:0;z-index:99;background:#fff;border-bottom:1px solid var(--border);box-shadow:0 6px 18px rgba(7,31,61,.05)}.header-inner{height:74px}.brand{display:flex;align-items:center;gap:12px;font-weight:900;color:var(--navy);font-size:28px}.brand-mark{width:45px;height:45px;border-radius:14px;background:linear-gradient(135deg,var(--navy),var(--blue));color:#fff;display:grid;place-items:center}.brand-mark span{color:var(--orange)}.nav,.nav ul{display:flex;gap:18px;align-items:center;margin:0;padding:0;list-style:none}.nav li{list-style:none}.nav a{font-size:14px}.btn{display:inline-flex;padding:12px 18px;border-radius:12px;background:var(--orange);color:#fff;font-weight:800;border:0;cursor:pointer}.btn.light{background:#fff;color:var(--navy);border:1px solid var(--border)}.hero{background:radial-gradient(circle at 85% 15%,rgba(255,138,0,.25),transparent 26%),linear-gradient(135deg,#061b35,#0b4f9c);color:#fff;padding:76px 0 58px}.hero-grid{display:grid;grid-template-columns:1.05fr .95fr;gap:38px;align-items:center}.eyebrow{display:inline-flex;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);padding:8px 12px;border-radius:999px;font-weight:800;font-size:13px}.hero h1{font-size:54px;line-height:1.04;margin:18px 0 16px}.hero p{font-size:18px;color:#dbe8ff}.hero-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:26px}.card,.search-panel{background:#fff;color:var(--text);border-radius:22px;padding:24px;box-shadow:0 20px 50px rgba(2,18,38,.12);border:1px solid var(--border)}.input,select,textarea{width:100%;padding:13px 14px;border:1px solid var(--border);border-radius:12px;font:inherit}textarea{min-height:110px}.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}.stats,.grid-4{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:28px}.stat{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.18);border-radius:16px;padding:16px}.stat b{font-size:25px;display:block}.section{padding:64px 0}.soft{background:var(--soft)}.section-title{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:28px}.section-title h2{font-size:34px;margin:0;color:var(--navy)}.section-title p{color:var(--muted);max-width:640px}.grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}.feature,.biz-card,.price-card{background:#fff;border:1px solid var(--border);border-radius:20px;padding:22px;box-shadow:0 10px 28px rgba(7,31,61,.06)}.feature .icon{width:48px;height:48px;border-radius:14px;background:#fff3e4;color:var(--orange);display:grid;place-items:center;font-size:24px;margin-bottom:12px}.feature h3,.biz-card h3{margin:0 0 8px;color:var(--navy)}.feature p,.biz-card p{margin:0;color:var(--muted)}.listing-layout{display:grid;grid-template-columns:280px 1fr;gap:24px}.sidebar{background:#fff;border:1px solid var(--border);border-radius:20px;padding:20px;height:max-content}.list-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px}.biz-head{display:flex;gap:14px}.avatar{width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,var(--blue),var(--orange));color:#fff;display:grid;place-items:center;font-weight:900;font-size:20px;flex:0 0 auto}.meta{display:flex;gap:8px;flex-wrap:wrap;margin:12px 0}.meta span,.pill{font-size:12px;background:#f1f5fa;border:1px solid var(--border);border-radius:999px;padding:5px 9px;color:#52657d}.verified{color:var(--green)!important;font-weight:900}.price{font-size:34px;font-weight:900;color:var(--navy);margin:12px 0}.featured{border:2px solid var(--orange)}.checklist{list-style:none;padding:0}.checklist li{padding:8px 0;border-bottom:1px dashed var(--border)}.checklist li:before{content:"✓";color:var(--green);font-weight:900;margin-right:8px}.dashboard{display:grid;grid-template-columns:260px 1fr;gap:24px}.dash-nav{background:var(--navy);color:#fff;border-radius:22px;padding:18px}.dash-nav a{display:block;padding:12px;border-radius:12px;color:#dbe8ff}.dash-panel{background:#fff;border:1px solid var(--border);border-radius:22px;padding:24px}.table{width:100%;border-collapse:collapse}.table th,.table td{padding:13px;border-bottom:1px solid var(--border);text-align:left}.table th{background:#f6f9fd;color:var(--navy)}.footer{background:#061b35;color:#dbe8ff;padding:48px 0 24px}.footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:24px}.footer h3,.footer h4{color:#fff}.footer a{display:block;margin:8px 0}.notice{padding:14px 16px;border-radius:14px;background:#ecfff6;border:1px solid #b7f1d4;color:#116741;margin-bottom:18px}.mobile-toggle{display:none}
@media(max-width:900px){.hero-grid,.listing-layout,.dashboard,.grid-3,.grid-4,.list-grid,.stats,.footer-grid,.form-row{grid-template-columns:1fr}.hero h1{font-size:40px}.nav{display:none;position:absolute;left:0;right:0;top:74px;background:#fff;flex-direction:column;padding:20px;border-bottom:1px solid var(--border)}.nav.open{display:flex}.mobile-toggle{display:inline-flex}}
</style>
</head>
<!DOCTYPE html>
<html >
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

</head>
<body >
<div class="topbar"><div class="container">
  <span>DoBajar — Local marketplace under Do Galaxy.</span>
  <span>Powered by Kusumit Universe · MyDoApp</span>
</div></div>
<header class="site-header"><div class="container header-inner">
  <a class="brand" href="/">
    <span class="brand-mark">B<span>o</span></span><span>DoBajar</span>
  </a>
  <nav class="nav" id="mainNav"><a href="/">Home</a><a href="/listings">Listings</a><a href="/join">Join</a></nav>
  <a class="btn" href="/join">Become a seller</a>
</div></header>


<section class="hero"><div class="container hero-grid"><div>
  <span class="eyebrow">बाज़ार खुले, व्यापार बढ़े</span>
  <h1>List products. Get found. Sell locally.</h1>
  <p>DoBajar is the marketplace planet of Do Galaxy — seller visibility and product discovery for everyday buyers.</p>
  <div class="hero-actions">
    <a class="btn" href="/join">Become a seller</a>
    <a class="btn light" href="/listings">Browse listings</a>
  </div>
</div>
<div class="search-panel">
  <h3>Find products</h3>
  <form action="/listings" method="get">
    <input class="input" name="q" placeholder="Product, city, category...">
    <br><br><button class="btn" type="submit">Search</button>
  </form>
</div></div></section>
<section class="section"><div class="container">
  <div class="section-title"><div><h2>What DoBajar does</h2><p>DoBajar is the marketplace planet of Do Galaxy — seller visibility and product discovery for everyday buyers.</p></div></div>
  <div class="grid-3"><div class="feature"><div class="icon">🛒</div><h3>Listings</h3><p>A public card for what you sell.</p></div><div class="feature"><div class="icon">🏪</div><h3>Seller identity</h3><p>Tied to DoUdyog when the firm is verified.</p></div><div class="feature"><div class="icon">🔍</div><h3>Discovery</h3><p>City and category search.</p></div><div class="feature"><div class="icon">📩</div><h3>Enquiries</h3><p>Buyers request. You respond.</p></div><div class="feature"><div class="icon">📦</div><h3>Local first</h3><p>Neighbourhood trade before national ads.</p></div><div class="feature"><div class="icon">🌌</div><h3>Do Galaxy</h3><p>From identity to customer in one universe.</p></div></div>
</div></section>


<footer class="footer"><div class="container footer-grid">
  <div><h3>DoBajar</h3><p>Local marketplace under Do Galaxy.</p></div>
  <div><h4>Platform</h4><a href="/">Home</a><a href="/listings">Listings</a><a href="/join">Join</a></div>
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
<div class="container" style="border-top:1px solid rgba(255,255,255,.12);margin-top:28px;padding-top:18px;color:#91a8c8">© 2026 DoBajar. A Kusumit Universe initiative.</div>
</footer>
</body></html>

