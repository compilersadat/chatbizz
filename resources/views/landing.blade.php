@extends('layouts.public')
@section('title','Order local. Fast delivery. – ChatBizz')
@section('content')
<section class="hero">
  <div class="container hero-wrap">
    <div>
      <span class="badge">New</span>
      <h1>Order local. <br>Fast, reliable delivery with <span style="color:var(--brand)">ChatBizz</span>.</h1>
      <p>Discover nearby shops, place orders in seconds, and track deliveries live. Secure payments powered by Razorpay.</p>
      <div class="hero-cta">
        <a class="btn btn-primary" href="#" aria-label="Download on Google Play">Get the app</a>
        <a class="btn btn-outline" href="{{ route('policies.index') }}">View policies</a>
      </div>
      <div class="kpis">
        <div class="kpi"><strong>5k+</strong><span class="muted">Local items</span></div>
        <div class="kpi"><strong>30‑45m</strong><span class="muted">Avg delivery</span></div>
        <div class="kpi"><strong>100%</strong><span class="muted">Secure payments</span></div>
      </div>
    </div>
    <div>
      <div class="hero-card">
        <img src="{{asset('img/landing.png')}}" alt="ChatBizz app preview">
        <p class="muted mt-3">Seamless checkout via Razorpay • Live order tracking • Saved addresses</p>
      </div>
    </div>
  </div>
</section>
@endsection