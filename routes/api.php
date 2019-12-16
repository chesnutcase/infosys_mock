<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('events/{event}/register/QR', 'EventController@registerQR');
Route::post('events/{event}/register/selfie', 'EventController@registerFace');
Route::get('events', 'EventController@index');
Route::get('events/{event}', 'EventController@show');
Route::post('events', 'EventController@store');
Route::put('events/{event}', 'EventController@update');
Route::delete('events/{event}', 'EventController@delete');

Route::post('attendance/QR', 'AttendanceController@takeAttendanceQR');
Route::post('attendance/selfie', 'AttendanceController@takeAttendanceSelfie');
