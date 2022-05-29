<?php

use App\Http\Controllers\Api\PosController;
use Illuminate\Support\Facades\Route;

    // your routes here
Route::post('/login', "UserController@app_login");
Route::get('/logout', "UserController@app_logout");

Route::post('/register', "UserController@app_register");

Route::get('/transaction_deposit/{user_id}', "UserController@app_transactionDeposit");
Route::get('/transaction_interest/{user_id}', "UserController@app_transactionInterest");
Route::get('/interest_log/{user_id}', "UserController@app_interestLog");

Route::post('/update_profile', "UserController@app_updateProfile");

Route::post('/post_profile', "UserController@app_getProfile");
Route::get('/user_plan', "UserController@app_plan");

Route::post('/invest', "UserController@app_invest");

Route::post('/changePassword', "UserController@app_changePassword");
Route::post('/home', "UserController@app_home");

Route::post('/depositInsert', "Gateway\PaymentController@app_depositInsert");
Route::get('/depositHistory/{user_id}', "Gateway\PaymentController@app_depositHistory");
Route::post('/depositPreview', "Gateway\PaymentController@app_depositPreview");
Route::post('/depositConfirm', "Gateway\PaymentController@app_depositConfirm");

Route::get('/otpnum', "UserController@app_getOtpnum");
Route::get('/test', "UserController@test");
