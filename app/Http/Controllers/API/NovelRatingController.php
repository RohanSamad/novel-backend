<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\NovelRatingResource;
use App\Models\Chapter;
use App\Models\NovelRating;
use App\Models\Novel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NovelRatingController extends Controller
{
    /**
     * Store or update a novel rating using novel ID or title.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // First validate common fields
            $validator = Validator::make($request->all(), [
                'novel_id' => 'required', // Can be ID or title
                'rating' => 'required|integer|min:1|max:5',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 422);
            }

            if (!auth()->check()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Find novel by ID or slug
            $novelIdentifier = $request->input('novel_id');
            $novel = is_numeric($novelIdentifier)
                ? Novel::find($novelIdentifier)
                : Novel::findBySlug($novelIdentifier);

            if (!$novel) {
                return response()->json([
                    'error' => 'Novel not found',
                    'searched' => $novelIdentifier,
                    'suggestion' => is_numeric($novelIdentifier) 
                        ? 'Check the novel ID' 
                        : 'Verify the title spelling and try exact match'
                ], 404);
            }

            $userId = auth()->id();

            // Store or update rating
            $rating = NovelRating::updateOrCreate(
                ['novel_id' => $novel->id, 'user_id' => $userId],
                ['rating' => $request->input('rating')]
            );

            // Update novel stats
            $this->updateNovelStats($novel->id);

            return response()->json([
                'data' => new NovelRatingResource($rating),
            ]);

        } catch (\Exception $e) {
            Log::error('Rating store error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
                'user' => auth()->id() ?? 'guest'
            ]);
            
            return response()->json([
                'error' => 'Failed to store rating',
                'details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update the novel stats based on current ratings.
     *
     * @param  int  $novelId
     * @return bool
     * @throws \Exception
     */
    private function updateNovelStats($novelId)
    {
        try {
            $novel = Novel::findOrFail($novelId);
            
            $ratings = NovelRating::where('novel_id', $novelId)->get();
            $ratingCount = $ratings->count();
            $averageRating = $ratingCount > 0 ? $ratings->avg('rating') : 0;
            $chapterCount = Chapter::where('novel_id', $novelId)->count();

            $statsData = [
                'title' => $novel->title,
                'chapter_count' => $chapterCount,
                'reader_count' => 0, // Or calculate from actual data
                'average_rating' => $averageRating,
                'rating_count' => $ratingCount,
                'total_views' => 0, // Or calculate from actual data
                'last_updated' => now(),
                'updated_at' => now(),
            ];

            $novel->stats()->updateOrCreate([], $statsData);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to update novel stats', [
                'novel_id' => $novelId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}