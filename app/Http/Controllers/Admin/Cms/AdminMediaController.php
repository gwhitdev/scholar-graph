<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\Medium;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminMediaController extends Controller
{
    /**
     * Display a listing of media.
     */
    public function index(): Response
    {
        $media = Medium::with('uploadedBy')->latest()->paginate(20);

        return Inertia::render('admin/media/index', [
            'media' => $media,
        ]);
    }

    /**
     * Store a newly uploaded media file.
     */
    public function store(Request $request): RedirectResponse
    {
        $allowedMimes = config('cms.media.mimes', []);
        $maxSize = config('cms.media.max_size', 10240);

        // Convert MIME types to extensions for Laravel's mimes rule
        $mimeToExt = [
            'image/jpeg' => 'jpeg,jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/svg+xml' => 'svg',
        ];
        $extensions = collect($allowedMimes)
            ->map(fn ($mime) => $mimeToExt[$mime] ?? '')
            ->filter()
            ->implode(',');

        $request->validate([
            'file' => [
                'required',
                'file',
                'max:'.$maxSize,
                'mimes:'.$extensions,
            ],
            'alt' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $request->file('file');

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        $path = $file->store('media', 'public');

        Medium::create([
            'disk' => 'public',
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'alt' => $request->input('alt'),
            'uploaded_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.media.index')
            ->with('success', 'Media uploaded successfully.');
    }

    /**
     * Remove the specified media.
     */
    public function destroy(Medium $medium): RedirectResponse
    {
        Storage::disk($medium->disk)->delete($medium->path);
        $medium->delete();

        return redirect()->route('admin.media.index')
            ->with('success', 'Media deleted successfully.');
    }
}
