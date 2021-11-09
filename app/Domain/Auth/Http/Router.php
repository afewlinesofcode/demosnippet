<?php


namespace App\Domain\Auth\Http;


use Illuminate\Support\Facades\Route;

class Router
{
    public static function instagramWebapiRoutes()
    {
        Route::group(
            [
                'namespace' => '\\' . __NAMESPACE__,
                'prefix' => 'instagram',
            ],
            function () {
                Route::get('/', 'InstagramController@show');
                Route::get('status', 'InstagramController@showStatus');
                Route::get('media', 'InstagramController@showMedia');
                Route::post('disconnect', 'InstagramController@disconnect');
            }
        );
    }

    public static function instagramApiRoutes()
    {
        Route::group(
            [
                'namespace' => '\\' . __NAMESPACE__,
                'prefix' => 'instagram',
            ],
            function () {
                Route::post('connect', 'InstagramController@connect');
                Route::post('disconnect', 'InstagramController@disconnect');
                Route::get('media', 'InstagramController@showMedia');
            }
        );
    }

    public static function instagramPublicRoutes()
    {
        Route::group(
            [
                'namespace' => '\\' . __NAMESPACE__,
                'prefix' => 'instagram',
            ],
            function () {
                Route::get('/callback', 'InstagramController@callback');
                Route::post('/leave', 'InstagramController@leave');
                Route::post('/clear', 'InstagramController@clear');
                Route::get('/deletion/{code}', 'InstagramController@showDeletionStatus');
            }
        );
    }

    public static function phoneRoutes()
    {
        Route::group(
            [
                'namespace' => '\\' . __NAMESPACE__,
                'prefix' => 'phone',
            ],
            function () {
                Route::post('/', 'PhoneController@store');
                Route::post('/complete', 'PhoneController@complete');
                Route::get('/resend', 'PhoneController@showResend');
                Route::post('/resend', 'PhoneController@resend');
            }
        );
    }

    public static function phoneApiRoutes()
    {
        Route::group(
            [
                'namespace' => '\\' . __NAMESPACE__,
                'prefix' => 'auth/phone',
            ],
            function () {
                Route::post('/', 'PhoneController@storeApi');
                Route::get('/resend', 'PhoneController@showResend');
                Route::post('/resend', 'PhoneController@resend');
            }
        );
    }
}
