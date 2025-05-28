<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChapterResource;
use App\Http\Resources\ChapterCollection;
use App\Models\Chapter;
use App\Models\Novel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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

            return response()->json($chapters);
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
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'novel_id' => 'required|integer|exists:novels,id',
            'chapter_number' => 'required|integer|min:1',
            'title' => 'required|string|max:255',
            'audio_url' => 'nullable|url',
            'content_text' => 'required|string|min:1',
            'order_index' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $chapter = Chapter::create($validator->validated());
            return new ChapterResource($chapter);
        } catch (\Exception $e) {
            Log::error('Failed to create chapter', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create chapter: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update an existing chapter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \App\Http\Resources\ChapterResource
     */
    public function update(Request $request, $id)
    {
        if (!is_numeric($id) || $id <= 0) {
            return response()->json(['error' => 'Invalid chapter ID'], 422);
        }

        $chapter = Chapter::find($id);
        if (!$chapter) {
            return response()->json(['error' => 'Chapter not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'novel_id' => 'sometimes|integer|exists:novels,id',
            'chapter_number' => 'sometimes|integer|min:1',
            'title' => 'sometimes|string|max:255',
            'audio_url' => 'nullable|url',
            'content_text' => 'sometimes|string|min:1',
            'order_index' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $chapter->update($validator->validated());
            return new ChapterResource($chapter);
        } catch (\Exception $e) {
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
            $chapter->delete();
            return response()->json(['message' => 'Chapter deleted successfully', 'id' => (int) $id]);
        } catch (\Exception $e) {
            Log::error('Failed to delete chapter', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete chapter: ' . $e->getMessage()], 500);
        }
    }
}