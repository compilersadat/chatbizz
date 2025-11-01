@extends('policies.layout')
@section('title','Policies & Legal')
@section('content')
<p>Welcome to ChatBizz’s legal and policy center. For Razorpay activation and user transparency, please review the pages below:</p>
<ul class="list">
  <li><a href="{{ route('policies.privacy') }}">Privacy Policy</a></li>
  <li><a href="{{ route('policies.terms') }}">Terms & Conditions</a></li>
  <li><a href="{{ route('policies.refund') }}">Cancellation & Refund Policy</a></li>
  <li><a href="{{ route('policies.delivery') }}">Delivery / Shipping Policy</a></li>
  <li><a href="{{ route('policies.contact') }}">Contact Us</a></li>
</ul>
@endsection