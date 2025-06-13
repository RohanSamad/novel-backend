<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\NovelRatingResource;
use App\Models\NovelRating;
use App\Models\NovelStats;
use App\Models\Novel;
use App\Models\Chapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NovelRatingController extends Controller
{
    /**
     * Store or update a novel rating.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Validate the request
            $data = $request->validate([
                'novel_id' => 'required|integer|exists:novels,id',
                'rating' => 'required|integer|min:1|max:5',
            ]);

            // Ensure the user is authenticated
            if (!auth()->check()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Get the authenticated user ID
            $userId = auth()->id();

            // Upsert the rating (update or create)
            $rating = NovelRating::updateOrCreate(
                [
                    'novel_id' => $data['novel_id'],
                    'user_id' => $userId,
                ],
                [
                    'rating' => $data['rating'],
                ]
            );

            // Update novel stats after rating change
            $this->updateNovelStats($data['novel_id']);

            // Return the updated rating
            return response()->json([
                'data' => new NovelRatingResource($rating),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store or update rating', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Update the novel stats based on current ratings.
     *
     * @param  int  $novelId
     * @return void
     */
    private function updateNovelStats($novelId)
    {
        try {
            $ratings = NovelRating::where('novel_id', $novelId)->get();
            $ratingCount = $ratings->count();
            $averageRating = $ratingCount > 0 ? $ratings->avg('rating') : 0;
            $chapterCount = Chapter::where('novel_id', $novelId)->count();

            // Ensure the novel exists
            $novel = Novel::find($novelId);
            if (!$novel) {
                throw new \Exception('Novel not found');
            }

            // Update or create the novel stats record
            NovelStats::updateOrCreate(
                ['id' => $novelId],
                [
                    'title' => $novel->title,
                    'chapter_count' => $chapterCount,
                    'reader_count' => 0, // Update this if you track readers
                    'average_rating' => $averageRating,
                    'rating_count' => $ratingCount,
                    'total_views' => 0, // Update this if you track views
                    'last_updated' => now(),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to update novel stats', [
                'novel_id' => $novelId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}