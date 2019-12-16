<?php

namespace App\Http\Controllers;

use App\Attendee;
use Aws\Lambda\LambdaClient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

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

    public function takeAttendanceSelfie(Request $request)
    {
        $request->validate([
            'selfie' => 'required|image',
        ]);
        $tmp_path = Storage::disk('s3')->put('tmp', $request->file('selfie'), 'public');
        $lambdaClient = LambdaClient::factory([
            'version' => 'latest',
            'region' => env('AWS_DEFAULT_REGION'),
        ]);
        $result = $lambdaClient->invoke([
            'FunctionName' => env('FACE_DETECTOR_LAMBDA'),
            'Payload' => json_encode([
                'bucket' => env('AWS_BUCKET'),
                'key' => $tmp_path,
            ]),
        ]);

        $result_payload = json_decode((string) $result->get('Payload'), true);

        if (array_key_exists('statusCode', $result_payload)) {
            if ($result_payload['statusCode'] == 404) {
                return response()->json($result_payload, 404);
            } elseif ($result_payload['statusCode'] == 200) {
                $match = Attendee::where('face', $result_payload['body']['face_id'])->first();
                if (is_null($match)) {
                    return response()->json([
                        'error' => 'face recognised, but not found in database',
                    ], 404);
                }
                $match->attended_at = Carbon::now();
                $match->save();

                return response()->json($match->only('name', 'email'));
            } else {
                return response()->json(['message' => 'something unexpected happened', 'error' => $result_payload], 500);
            }
        } else {
            return response()->json(['message' => 'something unexpected happened', 'error' => $result_payload], 500);
        }
    }
}
