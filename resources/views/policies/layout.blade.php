<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title') – ChatBizz</title>
  <meta name="robots" content="index,follow">
  <link rel="stylesheet" href="/css/chatbizz.css?v={{ substr(md5(filemtime(public_path('css/chatbizz.css'))),0,8) }}">
</head>
<body>
  <div class="container">
    <div class="card">
      <header>
        <h1 class="h1">@yield('title')</h1>
        <div class="muted">Effective date: 1 November 2025</div>
        <nav class="nav mt-2" style="justify-content:flex-start">
          <div class="nav-links">
            <a href="{{ route('policies.index') }}">Overview</a>
            <a href="{{ route('policies.privacy') }}">Privacy</a>
            <a href="{{ route('policies.terms') }}">Terms</a>
            <a href="{{ route('policies.refund') }}">Cancellation & Refunds</a>
            <a href="{{ route('policies.delivery') }}">Delivery/Shipping</a>
            <a href="{{ route('policies.contact') }}">Contact</a>
          </div>
        </nav>
      </header>
      <main>@yield('content')</main>
      <footer class="footer">© {{ date('Y') }} ChatBizz. All rights reserved.</footer>
    </div>
  </div>
</body>
</html>
