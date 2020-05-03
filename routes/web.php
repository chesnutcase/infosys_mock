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

Route::get('/attendee/{attendee}/deregister/{nonce}', function(App\Attendee $attendee, String $nonce){
   $event_name = $attendee->event->title;
   if($nonce === $attendee->delete_nonce){
       $attendee->delete();
       return view("deregistered", [
          "event_name" => $event_name
       ]);
   }
});
