<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title') – ChatBizz</title>
<meta name="robots" content="index,follow">
<style>
:root{--fg:#111827;--muted:#6b7280;--bg:#ffffff;--card:#f9fafb;--brand:#ef4444}
*{box-sizing:border-box} body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,"Apple Color Emoji","Segoe UI Emoji";color:var(--fg);background:var(--bg)}
a{color:var(--brand);text-decoration:none} a:hover{text-decoration:underline}
.container{max-width:920px;margin:0 auto;padding:24px}
.card{background:var(--card);border:1px solid #e5e7eb;border-radius:16px;padding:24px}
h1{font-size:1.875rem;margin:0 0 8px} h2{font-size:1.25rem;margin:24px 0 8px}
.muted{color:var(--muted)} .nav{display:flex;flex-wrap:wrap;gap:12px;margin:8px 0 0}
.footer{margin-top:32px;font-size:.9rem}
.list{padding-left:20px}
</style>
</head>
<body>
<div class="container">
<div class="card">
<header>
<h1>@yield('title')</h1>
<div class="muted">Effective date: 1 November 2025</div>
<nav class="nav">
<a href="{{ route('policies.index') }}">Overview</a>
<a href="{{ route('policies.privacy') }}">Privacy</a>
<a href="{{ route('policies.terms') }}">Terms</a>
<a href="{{ route('policies.refund') }}">Cancellation & Refunds</a>
<a href="{{ route('policies.delivery') }}">Delivery/Shipping</a>
<a href="{{ route('policies.contact') }}">Contact</a>
</nav>
</header>
<main>@yield('content')</main>
<footer class="footer muted">© {{ date('Y') }} ChatBizz. All rights reserved.</footer>
</div>
</div>
</body>
</html>