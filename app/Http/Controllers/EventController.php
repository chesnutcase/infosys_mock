<?php

namespace App\Http\Controllers;

use App\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        $event = Event::create($request->except(['picture', 'speaker_images']));

        $speaker_images_files = $request->file('speaker_images');
        $speaker_images_paths = [];
        foreach ($speaker_images_files as $speaker_image_file) {
            \Log::info('help la');
            \Log::info($speaker_image_file->getClientOriginalName());
            $path = Storage::disk('s3')->put("events/{$event->id}/speakers", $speaker_image_file);
            array_push($speaker_images_paths, $path);
        }
        if ($request->hasFile('picture')) {
            $path = Storage::disk('s3')->put("events/{$event->id}", $request->file('picture'));
            $event->picture = $path;
        }
        $event->speaker_images = $speaker_images_paths;
        $event->save();

        return response()->json($event, 201);
    }

    public function update(Request $request, Event $event)
    {
        $event->update($request->except(['picture', 'speaker_images']));

        $speaker_images_files = $request->file('speaker_images');
        $speaker_images_paths = [];
        foreach ($speaker_images_files as $speaker_image_file) {
            $path = Storage::disk('s3')->put("events/{$event->id}/speakers", $speaker_image_file);
            array_push($speaker_images_paths, $path);
        }
        if ($request->hasFile('picture')) {
            $path = Storage::disk('s3')->put("events/{$event->id}", $request->file('picture'));
            $event->picture = $path;
        }
        $event->speaker_images = $speaker_images_paths;
        $event->save();

        return response()->json($event, 200);
    }

    public function delete(Event $event)
    {
        $event->delete();

        return response()->json(null, 204);
    }
}
