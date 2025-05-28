<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeaturedNovelResource;
use App\Http\Resources\FeaturedNovelCollection;
use App\Models\FeaturedNovel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FeaturedNovelController extends Controller
{
    /**
     * Fetch all featured novels with novel details, ordered by position.
     *
     * @return \App\Http\Resources\FeaturedNovelCollection
     */
    public function index()
    {
        try {
            $featuredNovels = FeaturedNovel::with('novel')
                ->orderBy('position', 'asc')
                ->get();

            return new FeaturedNovelCollection($featuredNovels);
        } catch (\Exception $e) {
            Log::error('Failed to fetch featured novels', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch featured novels'], 500);
        }
    }

    /**
     * Add a new featured novel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Http\Resources\FeaturedNovelResource
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'novel_id' => 'required|integer|exists:novels,id',
            'position' => 'required|integer|min:1|unique:featured_novels,position',
            'start_date' => 'required|date|before:end_date',
            'end_date' => 'required|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $featuredNovel = FeaturedNovel::create($validator->validated());
            $featuredNovel->load('novel');
            return new FeaturedNovelResource($featuredNovel);
        } catch (\Exception $e) {
            Log::error('Failed to add featured novel', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to add featured novel'], 500);
        }
    }

    /**
     * Remove a featured novel.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        if (!is_numeric($id) || $id <= 0) {
            return response()->json(['error' => 'Invalid featured novel ID'], 422);
        }

        $featuredNovel = FeaturedNovel::find($id);
        if (!$featuredNovel) {
            return response()->json(['error' => 'Featured novel not found'], 404);
        }

        try {
            $featuredNovel->delete();
            return response()->json(['message' => 'Featured novel removed successfully', 'id' => (int) $id]);
        } catch (\Exception $e) {
            Log::error('Failed to remove featured novel', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to remove featured novel'], 500);
        }
    }
}