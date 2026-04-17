<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuestionImageController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user(), 401);
        abort_unless($request->user()->isTeacher(), 403);

        $validated = $request->validate([
            'image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp,gif',
                'max:2048',
                'dimensions:max_width=2400,max_height=2400',
            ],
        ]);

        $path = $validated['image']->store('question-images', 'public');

        return response()->json([
            'url' => route('question-images.show', ['path' => $path]),
        ]);
    }

    public function show(string $path): StreamedResponse
    {
        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }

    public function showLegacy(string $file): StreamedResponse
    {
        return $this->show('question-images/'.$file);
    }
}
