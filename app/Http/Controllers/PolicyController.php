<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PolicyController extends Controller
{
    public function index()
{
return view('policies.index');
}


public function privacy()
{
return view('policies.privacy');
}


public function terms()
{
return view('policies.terms');
}


public function refund()
{
return view('policies.refund');
}


public function delivery()
{
return view('policies.delivery');
}


public function contact()
{
return view('policies.contact');
}
}
