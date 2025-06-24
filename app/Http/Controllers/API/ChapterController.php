<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChapterRequest;
use App\Http\Requests\UpdateChapterRequest;
use App\Http\Resources\ChapterResource;
use App\Http\Resources\ChapterCollection;
use App\Models\Chapter;
use App\Models\Novel;
use Illuminate\Support\Facades\Log;
  use Illuminate\Support\Str;
use Storage;

class ChapterController extends Controller
{
   

    /**
 * Fetch all chapters for a novel (by ID or title), ordered by order_index.
 *
 * @param  string|int  $novelIdentifier
 * @return \App\Http\Resources\ChapterCollection|\Illuminate\Http\JsonResponse
 */
public function index($novelId)
{
    try {
        // Validate identifier
        if (empty($novelId)) {
            return response()->json(['error' => 'Novel identifier is required'], 422);
        }

        // Find the novel by ID or title
        $novel = is_numeric($novelId) 
            ? Novel::find($novelId)
            : Novel::where('title', urldecode($novelId))->first();

        if (!$novel) {
            return response()->json([
                'error' => 'Novel not found',
                'searched' => $novelId,
                'suggestion' => is_numeric($novelId) 
                    ? 'Check the novel ID' 
                    : 'Verify the title spelling and try exact match'
            ], 404);
        }

        // Get ordered chapters
        $chapters = Chapter::where('novel_id', $novel->id)
            ->orderBy('order_index', 'asc')
            ->get();

        return new ChapterCollection($chapters);

    } catch (\Exception $e) {
        Log::error('Failed to fetch chapters', [
            'novel_identifier' => $novelId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'error' => 'Failed to fetch chapters',
            'details' => env('APP_DEBUG') ? $e->getMessage() : null
        ], 500);
    }
}

    

  

/**
 * Fetch a single chapter by novel identifier and chapter identifier
 * 
 * @param string|int $novelIdentifier (ID or title)
 * @param string|int $chapterIdentifier (ID or chapter_number)
 * @return \App\Http\Resources\ChapterResource|\Illuminate\Http\JsonResponse
 */

 /*public function show($novelIdentifier, $chapterIdentifier)
  {
    try {
        // Validate identifiers
        if (empty($novelIdentifier) || empty($chapterIdentifier)) {
            return response()->json(['error' => 'Novel and chapter identifiers are required'], 422);
        }

        // First find the novel - handle URL-encoded titles
        if (is_numeric($novelIdentifier) && $novelIdentifier > 0) {
            $novel = Novel::find($novelIdentifier);
        } else {
            $cleanNovelIdentifier = urldecode(trim($novelIdentifier, '"\''));
            
            // Search only by title (since slug column doesn't exist)
            $novel = Novel::where('title', $cleanNovelIdentifier)->first();
            
            // For debugging - remove in production
            if (!$novel) {
                $allNovels = Novel::pluck('title')->toArray();
                Log::debug('Novel search failed', [
                    'searched_title' => $cleanNovelIdentifier,
                    'available_titles' => $allNovels
                ]);
            }
        }

        if (!$novel) {
            return response()->json([
                'error' => 'Novel not found',
                'searched_title' => $cleanNovelIdentifier ?? $novelIdentifier,
                'hint' => 'Make sure the title matches exactly, including capitalization'
            ], 404);
        }

        // Then find the chapter
        if (is_numeric($chapterIdentifier) && $chapterIdentifier > 0) {
            $chapter = Chapter::where('novel_id', $novel->id)
                            ->where(function($query) use ($chapterIdentifier) {
                                $query->where('id', $chapterIdentifier)
                                      ->orWhere('chapter_number', $chapterIdentifier);
                            })
                            ->first();
        } else {
            $cleanChapterIdentifier = urldecode(trim($chapterIdentifier, '"\''));
            $chapter = Chapter::where('novel_id', $novel->id)
                            ->where('title', $cleanChapterIdentifier)
                            ->first();
        }

        if (!$chapter) {
            return response()->json([
                'error' => 'Chapter not found for this novel',
                'novel_id' => $novel->id,
                'novel_title' => $novel->title,
                'searched_chapter' => is_numeric($chapterIdentifier) 
                    ? $chapterIdentifier 
                    : ($cleanChapterIdentifier ?? $chapterIdentifier),
                'available_chapters' => Chapter::where('novel_id', $novel->id)
                                            ->pluck('title', 'chapter_number')
                                            ->toArray()
            ], 404);
        }

        return new ChapterResource($chapter);
    } catch (\Exception $e) {
        Log::error('Failed to fetch chapter', [
            'novel_identifier' => $novelIdentifier,
            'chapter_identifier' => $chapterIdentifier,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'error' => 'Failed to fetch chapter',
            'details' => env('APP_DEBUG') ? $e->getMessage() : null
        ], 500);
    }
}*/
public function show($novelId, $chapterId)
{
    try {
        // Validate inputs
        if (empty($novelId) || empty($chapterId)) {
            return response()->json(['error' => 'Novel and chapter identifiers are required'], 422);
        }

        // Find novel - tries ID first, then title
        $novel = Novel::when(is_numeric($novelId), function($query) use ($novelId) {
                    return $query->where('id', $novelId);
                }, function($query) use ($novelId) {
                    return $query->where('title', urldecode($novelId));
                })
                ->first();

        if (!$novel) {
            return response()->json(['error' => 'Novel not found'], 404);
        }

        // Find chapter - tries ID first, then number, then title
        $chapter = Chapter::where('novel_id', $novel->id)
                ->where(function($query) use ($chapterId) {
                    $query->where('id', $chapterId)
                          ->orWhere('chapter_number', $chapterId)
                          ->when(!is_numeric($chapterId), function($q) use ($chapterId) {
                              $q->orWhere('title', urldecode($chapterId));
                          });
                })
                ->first();

        if (!$chapter) {
            return response()->json(['error' => 'Chapter not found'], 404);
        }

        return new ChapterResource($chapter);

    } catch (\Exception $e) {
        Log::error('Failed to fetch chapter', [
            'novelId' => $novelId,
            'chapterId' => $chapterId,
            'error' => $e->getMessage()
        ]);
        return response()->json(['error' => 'Failed to fetch chapter'], 500);
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