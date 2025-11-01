<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','ChatBizz')</title>
  <meta name="robots" content="index,follow">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/css/chatbizz.css?v={{ substr(md5(filemtime(public_path('css/chatbizz.css'))),0,8) }}">
</head>
<body>
  <header class="header">
    <div class="container nav">
      <a href="{{ url('/') }}" class="brand" aria-label="ChatBizz home">
        <img class="brand-logo" src="{{asset('img/taxifyLogo.jpeg')}}"/>
        <span>ChatBizz</span>
      </a>
      <nav class="nav-links" aria-label="Primary">
        <a href="{{ route('policies.index') }}">Policies</a>
        <a href="{{ route('policies.privacy') }}">Privacy</a>
        <a href="{{ route('policies.terms') }}">Terms</a>
        <a href="{{ route('policies.contact') }}">Contact</a>
      </nav>
    </div>
  </header>

  <main>@yield('content')</main>

  <footer class="container footer">© {{ date('Y') }} ChatBizz • Built with ♥ in Nanded</footer>
</body>
</html>