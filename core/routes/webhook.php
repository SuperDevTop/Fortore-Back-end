<?php


use App\Http\Controllers\Gateway\my_fatoorah\WebhookController;
use Illuminate\Support\Facades\Route;


// baseUrl/webhook/myfatoorah/status_changed
Route::group(['prefix' => 'oorah', 'middleware' => "MyFatoorahWebhookAccessKeyMiddleware"], function () {
    Route::post('status_changed', [WebhookController::class, 'statusChanged']);
});
