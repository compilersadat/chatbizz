<?php

use Illuminate\Support\Facades\Route;
// use App\Filament\Pages\Settings;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PolicyController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});



Route::controller(PolicyController::class)->group(function () {
Route::get('/policies', 'index')->name('policies.index');
Route::get('/policies/privacy', 'privacy')->name('policies.privacy');
Route::get('/policies/terms', 'terms')->name('policies.terms');
Route::get('/policies/refund', 'refund')->name('policies.refund');
Route::get('/policies/delivery', 'delivery')->name('policies.delivery');
Route::get('/policies/contact', 'contact')->name('policies.contact');
});

// Route::get('/keycloak/callback', function () {
//     return view('welcome');
// });

Route::get('/keycloak/callback', [Controller::class, 'callback']);


// Settings::route('/settings', 'settings');