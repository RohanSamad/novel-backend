<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChapterRequest;
use App\Http\Requests\UpdateChapterRequest;
use App\Http\Resources\ChapterResource;
use App\Http\Resources\ChapterCollection;
use App\Models\Chapter;
use Illuminate\Support\Facades\Log;
use Storage;

class ChapterController extends Controller
{
    /**
     * Fetch all chapters for a novel, ordered by order_index.
     *
     * @param  int  $novelId
     * @return \App\Http\Resources\ChapterCollection
     */
    public function index($novelId)
    {
        try {
            if (!is_numeric($novelId) || $novelId <= 0) {
                return response()->json(['error' => 'Invalid novel ID'], 422);
            }

            $chapters = Chapter::where('novel_id', $novelId)
                ->orderBy('order_index', 'asc')
                ->get();

            return new ChapterCollection($chapters);
        } catch (\Exception $e) {
            Log::error('Failed to fetch chapters', ['novel_id' => $novelId, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch chapters: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Fetch a single chapter by ID and novel ID.
     *
     * @param  int  $novelId
     * @param  int  $chapterId
     * @return \App\Http\Resources\ChapterResource
     */
    public function show($novelId, $chapterId)
    {
        try {
            if (!is_numeric($novelId) || $novelId <= 0 || !is_numeric($chapterId) || $chapterId <= 0) {
                return response()->json(['error' => 'Invalid novel or chapter ID'], 422);
            }

            $chapter = Chapter::where('novel_id', $novelId)
                ->where('id', $chapterId)
                ->first();

            if (!$chapter) {
                return response()->json(['error' => 'Chapter not found'], 404);
            }

            return new ChapterResource($chapter);
        } catch (\Exception $e) {
            Log::error('Failed to fetch chapter', ['novel_id' => $novelId, 'chapter_id' => $chapterId, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch chapter: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Fetch the 20 most recent chapters with novel titles.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function recent()
    {
        try {
            $chapters = Chapter::with('novel')
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get()
                ->map(function ($chapter) {
                    return [
                        'id' => (int) $chapter->id,
                        'novel_id' => (int) $chapter->novel_id,
                        'chapter_number' => $chapter->chapter_number,
                        'title' => $chapter->title,
                        'audio_url' => $chapter->audio_url,
                        'content_text' => $chapter->content_text,
                        'order_index' => $chapter->order_index,
                        'created_at' => $chapter->created_at->toIso8601String(),
                        'updated_at' => $chapter->updated_at->toIso8601String(),
                        'novel_title' => $chapter->novel->title ?? null,
                    ];
                });

            return response()->json(data: $chapters);
        } catch (\Exception $e) {
            Log::error('Failed to fetch recent chapters', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch recent chapters: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Create a new chapter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Http\Resources\ChapterResource
     */
    /**
     * Create a new chapter.
     *
     * @param  \App\Http\Requests\StoreChapterRequest  $request
     * @return \App\Http\Resources\ChapterResource
     */
    public function store(StoreChapterRequest $request)
    {
        try {
            $data = $request->validated();

            // Handle audio file upload
                $audioUrl = null;
            if ($request->hasFile('audio_file')) {
                $file = $request->file('audio_file');
                $timestamp = time();
                $fileExt = $file->getClientOriginalExtension();
                $fileName = "{$timestamp}.{$fileExt}";
                $path = $file->storeAs('', $fileName, 'novel_chapter_audio_bucket');
                $audioUrl = "novel_chapter_audio_bucket/{$fileName}"; // Store relative path
            }
            // Create the chapter
            $chapter = Chapter::create([
                'novel_id' => $data['novel_id'],
                'chapter_number' => $data['chapter_number'],
                'title' => $data['title'],
                'audio_url' => $audioUrl??"",
                'content_text' => $data['content_text'],
                'order_index' => $data['order_index'],
            ]);

            return new ChapterResource($chapter);
        } catch (\Exception $e) {
            // Roll back audio upload if chapter creation fails
            if (isset($fileName) && Storage::disk('novel_chapter_audio_bucket')->exists($fileName)) {
                Storage::disk('novel_chapter_audio_bucket')->delete($fileName);
            }
            Log::error('Failed to create chapter', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create chapter: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update an existing chapter.
     *
     * @param  \App\Http\Requests\UpdateChapterRequest  $request
     * @param  int  $id
     * @return \App\Http\Resources\ChapterResource
     */
    public function update(UpdateChapterRequest $request, $id)
    {
        if (!is_numeric($id) || $id <= 0) {
            return response()->json(['error' => 'Invalid chapter ID'], 422);
        }

        $chapter = Chapter::find($id);
        if (!$chapter) {
            return response()->json(['error' => 'Chapter not found'], 404);
        }

        try {
            $data = $request->validated();

            // Handle audio file upload
            if ($request->hasFile('audio_file')) {
                // Delete old audio file if it exists
                if ($chapter->audio_url) {
                    $oldFileName = basename($chapter->audio_url);
                    Storage::disk('novel_chapter_audio_bucket')->delete($oldFileName);
                }

                $file = $request->file('audio_file');
                $timestamp = time();
                $fileExt = $file->getClientOriginalExtension();
                $fileName = "{$timestamp}.{$fileExt}";
                $path = $file->storeAs('', $fileName, 'novel_chapter_audio_bucket');
                $data['audio_url'] = Storage::disk('novel_chapter_audio_bucket')->url($fileName);
            }

            // Update the chapter
            $chapter->update($data);

            return new ChapterResource($chapter);
        } catch (\Exception $e) {
            // Roll back audio upload if update fails
            if (isset($fileName) && Storage::disk('novel_chapter_audio_bucket')->exists($fileName)) {
                Storage::disk('novel_chapter_audio_bucket')->delete($fileName);
            }
            Log::error('Failed to update chapter', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update chapter: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a chapter.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        if (!is_numeric($id) || $id <= 0) {
            return response()->json(['error' => 'Invalid chapter ID'], 422);
        }

        $chapter = Chapter::find($id);
        if (!$chapter) {
            return response()->json(['error' => 'Chapter not found'], 404);
        }

        try {
            // Delete audio file if it exists
            if ($chapter->audio_url) {
                $fileName = basename($chapter->audio_url);
                Storage::disk('novel_chapter_audio_bucket')->delete($fileName);
            }

            $chapter->delete();
            return response()->json(['message' => 'Chapter deleted successfully', 'id' => (int) $id]);
        } catch (\Exception $e) {
            Log::error('Failed to delete chapter', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete chapter: ' . $e->getMessage()], 500);
        }
    }
} 