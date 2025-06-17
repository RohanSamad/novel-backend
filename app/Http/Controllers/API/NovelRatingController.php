<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\NovelRatingResource;
use App\Http\Resources\NovelStatsResource;
use App\Models\NovelRating;
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
        $data = $request->validate([
            'novel_id' => 'required|integer|exists:novels,id',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = auth()->id();

        $rating = NovelRating::updateOrCreate(
            ['novel_id' => $data['novel_id'], 'user_id' => $userId],
            ['rating' => $data['rating']]
        );

        $this->updateNovelStats($data['novel_id']);
        

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
            'error' => $e->getMessage(),
            'novel_id' => $data['novel_id'] ?? 'unknown'
        ], 400);
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
        $novel = Novel::findOrFail($novelId); // This will throw if novel doesn't exist
        
        $ratings = NovelRating::where('novel_id', $novelId)->get();
        $ratingCount = $ratings->count();
        $averageRating = $ratingCount > 0 ? $ratings->avg('rating') : 0;
        $chapterCount = Chapter::where('novel_id', $novelId)->count();

        $statsData = [
            'title' => $novel->title,
            'chapter_count' => $chapterCount,
            'reader_count' => 0, // Or calculate this if you have reader data
            'average_rating' => $averageRating,
            'rating_count' => $ratingCount,
            'total_views' => 0, // Or calculate this if you have view data
            'last_updated' => now(),
            'updated_at' => now(),
        ];

        // This will either update existing or create new stats
        $novel->stats()->updateOrCreate([], $statsData);

        return true;

    } catch (\Exception $e) {
        Log::error('Failed to update novel stats', [
            'novel_id' => $novelId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        throw $e; // Re-throw to be caught by the calling method
    }
}

}