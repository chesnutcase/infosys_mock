<?php

namespace App\Http\Controllers;

use App\Attendee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class AttendanceController extends Controller
{
    public function takeAttendanceQR(Request $request)
    {
        $request->validate([
            'qr_secret' => 'required',
        ]);
        $hashes = Attendee::pluck('id', 'qr_hash');
        $match = Arr::first($hashes, function ($value, $key) use ($request) {
            // $value is id, $key is qr_hash
            return Hash::check($request->input('qr_secret'), $key);
        }, null);
        if (!is_null($match)) {
            $attendee = Attendee::find($match);
            $attendee->attended_at = Carbon::now();
            $attendee->save();

            return response()->json($attendee->only(['name', 'email']));
        } else {
            return response()->json(['message' => 'not found'], 404);
        }
    }
}
