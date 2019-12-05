<?php

namespace App\Http\Controllers;

use App\Attendee;
use App\Event;
use Aws\Lambda\LambdaClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index()
    {
        return Event::all();
    }

    public function show(Event $event)
    {
        return $event;
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|max:255',
            'description' => 'required',
            'location' => 'required',
            'picture' => 'required|image',
            'speaker_images.*' => 'required|image',
            'start' => 'date_format:Y-m-d H:i',
            'end' => 'date_format:Y-m-d H:i',
        ]);
        $event = Event::create($request->except(['picture', 'speaker_images']));

        $speaker_images_files = $request->file('speaker_images');
        $speaker_images_paths = [];
        foreach ($speaker_images_files as $speaker_image_file) {
            \Log::info('help la');
            \Log::info($speaker_image_file->getClientOriginalName());
            $path = Storage::disk('s3')->put("events/{$event->id}/speakers", $speaker_image_file, 'public');
            array_push($speaker_images_paths, $path);
        }
        if ($request->hasFile('picture')) {
            $path = Storage::disk('s3')->put("events/{$event->id}", $request->file('picture'), 'public');
            $event->picture = $path;
        }
        $event->speaker_images = $speaker_images_paths;
        $event->save();

        return response()->json($event->fresh(), 201);
    }

    public function update(Request $request, Event $event)
    {
        $event->update($request->except(['picture', 'speaker_images']));

        $speaker_images_files = $request->file('speaker_images');
        $speaker_images_paths = [];
        foreach ($speaker_images_files as $speaker_image_file) {
            $path = Storage::disk('s3')->put("events/{$event->id}/speakers", $speaker_image_file, 'public');
            array_push($speaker_images_paths, $path);
        }
        if ($request->hasFile('picture')) {
            $path = Storage::disk('s3')->put("events/{$event->id}", $request->file('picture'), 'public');
            $event->picture = $path;
        }
        $event->speaker_images = $speaker_images_paths;
        $event->save();

        return response()->json($event->fresh(), 200);
    }

    public function delete(Event $event)
    {
        $event->delete();

        return response()->json(null, 204);
    }

    public function registerQR(Request $request, Event $event)
    {
        $attendee = $this->newAttendee($request, $event);
        if (!is_null($event->max_pax) && $event->attendees()->count() >= $event->max_pax) {
            if ($event->accept_waitlist) {
                // set waitlist_no to non null, DB trigger will handle the rest
                $attendee->waitlist_no = 1;
                $attendee->save();

                return response()->json([
                    'error' => 'waitlist',
                ], 400);
            } else {
                return response()->json([
                    'error' => 'full',
                ], 400);
            }
        } else {
            $randomFilename = Str::random(50);
            $qrPath = "qrcodes/${randomFilename}.png";
            $qrSecret = Str::random(50);
            $qrHash = bcrypt($qrSecret);
            $attendee->qr_hash = $qrHash;
            $lambdaClient = LambdaClient::factory([
                'version' => 'latest',
                'region' => env('AWS_DEFAULT_REGION'),
            ]);
            $lambdaArgs = [
                'key' => $qrPath,
                'secret' => $qrSecret,
                'mode' => $request->input('mode') ?? 'oka',
                'colorful' => $request->has('colorful') ? filter_var($request->input('colorful'), FILTER_VALIDATE_BOOLEAN) : true,
            ];
            $result = $lambdaClient->invoke([
                'FunctionName' => env('QR_GENERATOR_LAMBDA'),
                'Payload' => json_encode($lambdaArgs),
            ]);
            $attendee->save();

            return [
                'qr_link' => json_decode((string) $result->get('Payload'), true)['body'],
                'message' => 'success',
            ];
        }
    }

    public function registerFace(Request $request, Event $event)
    {
        $attendee = $this->newAttendee($request, $event);
        $request->validate([
            'selfie' => 'required|image',
        ]);
        if (!is_null($event->max_pax) && $event->attendees()->count() >= $event->max_pax) {
            if ($event->accept_waitlist) {
                // set waitlist_no to non null, DB trigger will handle the rest
                $attendee->waitlist_no = 1;
                $attendee->save();

                return response()->json([
                    'error' => 'waitlist',
                ], 400);
            } else {
                return response()->json([
                    'error' => 'full',
                ], 400);
            }
        } else {
            $tmp_path = Storage::disk('s3')->put('tmp', $request->file('selfie'), 'public');
            $lambdaClient = LambdaClient::factory([
                'version' => 'latest',
                'region' => env('AWS_DEFAULT_REGION'),
            ]);
            $result = $lambdaClient->invoke([
                'FunctionName' => env('FACE_TRAINER_LAMBDA'),
                'Payload' => json_encode([
                    'bucket' => env('AWS_BUCKET'),
                    'key' => $tmp_path,
                ]),
            ]);

            $result_payload = json_decode((string) $result->get('Payload'), true);

            if (array_key_exists('statusCode', $result_payload) && $result_payload['statusCode'] == 422) {
                return response()->json($result_payload['body']);
            } elseif (array_key_exists('statusCode', $result_payload) && $result_payload['statusCode'] == 200) {
                $attendee->face = $result_payload['body']['face_id'];
                $attendee->save();

                return response()->json([
                    'message' => 'success',
                ]);
            } else {
                return response()->json([
                    'message' => 'something unexpected happened',
                    'error' => $result_payload,
                ], 500);
            }
        }
    }

    private function newAttendee(Request $request, Event $event)
    {
        $data = array_merge($request->validate([
            'email' => 'required|email',
            'name' => 'required',
        ]), ['event_id' => $event->id]);
        $attendee = Attendee::make($data);

        return $attendee;
    }
}
