<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

//for gmail inbox demo
Route::get('gmailapi', 'Admin\MailController@gmailApiCall');
Route::get('gmailapi/callback', 'Admin\MailController@gmailApiCallback');
Route::get('gmailapi/useremail/{id}', 'Admin\MailController@getEmailById');

