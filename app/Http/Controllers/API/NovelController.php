<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\NovelResource;
use App\Http\Resources\NovelCollection;
use App\Models\Novel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class NovelController extends Controller
{
    /**
     * Fetch all novels with genres, ordered by created_at.
     *
     * @return \App\Http\Resources\NovelCollection
     */
    public function index()
    {
        try {
            $novels = Novel::with('genres', 'author')
                ->orderBy('created_at', 'desc')
                ->get();

            return new NovelCollection($novels);
        } catch (\Exception $e) {
            Log::error('Failed to fetch novels', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch novels: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Fetch a single novel by ID with genres.
     *
     * @param  int  $id
     * @return \App\Http\Resources\NovelResource
     */
    public function show($id)
    {
        try {
            if (!is_numeric($id) || $id <= 0) {
                return response()->json(['error' => 'Invalid novel ID'], 422);
            }

            $novel = Novel::with('genres', 'author')->find($id);

            if (!$novel) {
                return response()->json(['error' => 'Novel not found'], 404);
            }

            return new NovelResource($novel);
        } catch (\Exception $e) {
            Log::error('Failed to fetch novel', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch novel: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Fetch novels by author ID with genres.
     *
     * @param  int  $authorId
     * @return \App\Http\Resources\NovelCollection
     */
    public function byAuthor($authorId)
    {
        try {
            if (!is_numeric($authorId) || $authorId <= 0) {
                return response()->json(['error' => 'Invalid author ID'], 422);
            }

            $novels = Novel::with('genres', 'author')
                ->where('author_id', $authorId)
                ->orderBy('created_at', 'desc')
                ->get();

            return new NovelCollection($novels);
        } catch (\Exception $e) {
            Log::error('Failed to fetch novels by author', ['author_id' => $authorId, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch novels: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a novel and its cover image.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        if (!is_numeric($id) || $id <= 0) {
            return response()->json(['error' => 'Invalid novel ID'], 422);
        }

        $novel = Novel::find($id);
        if (!$novel) {
            return response()->json(['error' => 'Novel not found'], 404);
        }

        try {
            // Delete cover image if it exists
            if ($novel->cover_image_url) {
                $fileName = basename($novel->cover_image_url);
                Storage::disk('public')->delete('novel-covers/' . $fileName);
            }

            $novel->delete();
            return response()->json(['message' => 'Novel deleted successfully', 'id' => (int) $id]);
        } catch (\Exception $e) {
            Log::error('Failed to delete novel', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete novel: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Search novels by title.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Http\Resources\NovelCollection
     */
    public function search(Request $request)
    {
        $query = $request->query('q');
        if (!$query) {
            try {
                $novels = Novel::with('genres', 'author')
                    ->orderBy('created_at', 'desc')
                    ->get();
                return new NovelCollection($novels);
            } catch (\Exception $e) {
                Log::error('Failed to fetch all novels', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Failed to fetch novels: ' . $e->getMessage()], 500);
            }
        }

        try {
            $searchTerms = explode(' ', strtolower($query));
            $novels = Novel::with('genres', 'author')
                ->where(function ($q) use ($searchTerms) {
                    foreach ($searchTerms as $term) {
                        if ($term) {
                            $q->whereRaw('LOWER(title) LIKE ?', ['%' . $term . '%']);
                        }
                    }
                })
                ->orderBy('created_at', 'desc')
                ->get();

            return new NovelCollection($novels);
        } catch (\Exception $e) {
            Log::error('Failed to search novels', ['query' => $query, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to search novels: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Filter novels by genre slug.
     *
     * @param  string  $genreSlug
     * @return \App\Http\Resources\NovelCollection
     */
    public function byGenre($genreSlug)
    {
        try {
            $novels = Novel::with('genres', 'author')
                ->whereHas('genres', function ($q) use ($genreSlug) {
                    $q->where('slug', $genreSlug);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            return new NovelCollection($novels);
        } catch (\Exception $e) {
            Log::error('Failed to filter novels by genre', ['genre_slug' => $genreSlug, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to filter novels: ' . $e->getMessage()], 500);
        }
    }
}