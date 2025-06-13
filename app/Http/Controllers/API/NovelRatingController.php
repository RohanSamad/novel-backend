<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\NovelRatingResource;
use App\Models\NovelRating;
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
}