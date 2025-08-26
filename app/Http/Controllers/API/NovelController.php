<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNovelRequest;
use App\Http\Resources\NovelResource;
use App\Http\Resources\NovelCollection;
use App\Http\Resources\NovelStatsResource;
use App\Models\Author;
use App\Models\Chapter;
use App\Models\Genre;
use App\Models\Novel;
use App\Models\NovelRating;
use App\Models\NovelStats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NovelController extends Controller
{

    /**
     * Store a new novel.
     *
     * @param  \App\Http\Requests\StoreNovelRequest  $request
     * @return \App\Http\Resources\NovelResource
     */
public function store(StoreNovelRequest $request)
{
    try {
        Log::info('Starting novel creation process');
        
        $data = $request->validated();

        // Check or create author
        $author = Author::firstOrCreate(
            ['name' => $data['author']],
            ['name' => $data['author']]
        );
        $authorId = $author->id;
        
        Log::info('Author processed', ['author_id' => $authorId]);

        // Handle cover image upload
        $file = $request->file('cover_image');
        
        Log::info('File details', [
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'temp_path' => $file->getPathname()
        ]);

        $timestamp = time();
        $fileExt = $file->getClientOriginalExtension();
        $fileName = "{$timestamp}.{$fileExt}";
        
        Log::info('Generated filename', ['filename' => $fileName]);

        // Log S3 configuration
        $s3Config = config('filesystems.disks.s3');
        Log::info('S3 Configuration', [
            'endpoint' => $s3Config['endpoint'] ?? 'not set',
            'region' => $s3Config['region'] ?? 'not set',
            'bucket' => $s3Config['bucket'] ?? 'not set',
            'use_path_style_endpoint' => $s3Config['use_path_style_endpoint'] ?? 'not set'
        ]);

        Log::info('Attempting S3 upload', [
            'bucket_path' => 'novel_covers',
            'filename' => $fileName,
            'full_path' => "novel_covers/{$fileName}"
        ]);

        try {
            // Upload to S3 with correct visibility and MIME type
            $path = Storage::disk('s3')->putFileAs(
                'novel_covers',
                $file,
                $fileName,
                [
                    'visibility' => 'public',
                    'ContentType' => $file->getMimeType(),
                ]
            );
        } catch (\Aws\Exception\AwsException $awsException) {
            Log::error('AWS S3 Exception', [
                'aws_error_code' => $awsException->getAwsErrorCode(),
                'aws_error_type' => $awsException->getAwsErrorType(),
                'aws_error_message' => $awsException->getAwsErrorMessage(),
                'status_code' => $awsException->getStatusCode(),
                'request_id' => $awsException->getAwsRequestId()
            ]);
            throw $awsException;
        } catch (\Exception $uploadException) {
            Log::error('Upload Exception Details', [
                'exception_class' => get_class($uploadException),
                'message' => $uploadException->getMessage(),
                'code' => $uploadException->getCode(),
                'previous_exception' => $uploadException->getPrevious() ? get_class($uploadException->getPrevious()) : null,
                'previous_message' => $uploadException->getPrevious() ? $uploadException->getPrevious()->getMessage() : null
            ]);
            throw $uploadException;
        }
        
        Log::info('S3 upload successful', ['path' => $path]);

        // Get full public URL from S3
        $coverImageUrl = Storage::disk('s3')->url($path);
        
        Log::info('Generated cover URL', ['url' => $coverImageUrl]);

        // Create the novel
        $novel = Novel::create([
            'title' => $data['title'],
            'author' => $data['author'],
            'author_id' => $authorId,
            'publisher' => $data['publisher'],
            'cover_image_url' => $coverImageUrl, // ✅ Save full URL
            'synopsis' => $data['synopsis'],
            'status' => $data['status'],
            'publishing_year' => $data['publishing_year'],
        ]);
        
        Log::info('Novel created', ['novel_id' => $novel->id]);

        // Attach genres
        $novel->genres()->attach($data['genres']);

        // Load relationships for response
        $novel->load('genres', 'author');
        
        Log::info('Novel creation completed successfully');

        return new NovelResource($novel);

    } catch (\Exception $e) {
        Log::error('Failed to create novel', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json(['error' => $e->getMessage()], 400);
    }
}
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
     * Fetch a single novel by ID or title with genres, author, and stats.
     *
     * @param  string|int  $id (can be ID or title)
     * @return \Illuminate\Http\JsonResponse
     */

    public function show($id, Request $request)
    {
        try {
            $shortQuery = filter_var($request->query('short_query', false), FILTER_VALIDATE_BOOLEAN);
            $chapterLimit = (int) $request->query('limit', 0);

            // Optimize chapter selection based on short_query
            $chapterFields = $shortQuery
                ? ['id', 'novel_id', 'chapter_number', 'title', 'order_index', 'created_at', 'updated_at']
                : ['*'];

            // Build optimized chapters query
            $chaptersQuery = function ($query) use ($chapterLimit, $chapterFields) {
                $query->select($chapterFields)
                    ->orderBy('order_index');
                if ($chapterLimit > 0) {
                    $query->limit($chapterLimit);
                }
            };

            // Build main query
            if (is_numeric($id) && $id > 0) {
                $novel = Novel::with([
                    'genres:id,name,slug',  // Select only needed fields
                    'author:id,name',
                    'chapters' => $chaptersQuery,
                    'ratings:id,user_id,rating,created_at,updated_at,novel_id',
                    'featured:id,position,start_date,end_date,novel_id'
                ])->find($id);
            } else {
                $cleanIdentifier = trim($id, '"\'');
                $novel = Novel::with([
                    'genres:id,name,slug',
                    'author:id,name',
                    'chapters' => $chaptersQuery,
                    'ratings:id,user_id,rating,created_at,updated_at,novel_id',
                    'featured:id,position,start_date,end_date,novel_id'
                ])->where('title', $cleanIdentifier)
                    ->first();
            }

            if (!$novel) {
                return response()->json(['error' => 'Novel not found'], 404);
            }

            // Efficiently get chapter stats
            $totalChapters = null;
            $latestChapter = null;

            if ($chapterLimit > 0 && $novel->chapters->count() >= $chapterLimit) {
                // If we hit the limit, we need separate queries for accurate totals
                $totalChapters = \App\Models\Chapter::where('novel_id', $novel->id)->count();
                $latestChapter = \App\Models\Chapter::select($chapterFields)
                    ->where('novel_id', $novel->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
            } else {
                // We loaded all chapters, so derive from loaded data
                $totalChapters = $novel->chapters->count();
                $latestChapter = $novel->chapters->sortByDesc('created_at')->first();
            }

            $stats = $this->getNovelStats($novel->id);

            return response()->json([
                'data' => new NovelResource($novel, $shortQuery, $totalChapters, $latestChapter),
                'stats' => new NovelStatsResource($stats),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch novel', ['identifier' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch novel'], 500);
        }
    }

    /**
     * Update an existing novel.
     */
    // public function update(Request $request, $id)
    // {
    //     try {
    //         if (!is_numeric($id) || $id <= 0) {
    //             return response()->json(['error' => 'Invalid novel ID'], 422);
    //         }

    //         $novel = Novel::find($id);
    //         if (!$novel) {
    //             return response()->json(['error' => 'Novel not found'], 404);
    //         }

    //         $data = $request->validate([
    //             'title' => 'required|string|max:255',
    //             'author' => 'required|string|max:255',
    //             'publisher' => 'required|string|max:255',
    //             'publishing_year' => 'required|integer|min:1800|max:' . (date('Y') + 1),
    //             'cover_image' => 'nullable|image|max:2048', // For file upload
    //             // 'cover_image' => 'nullable|image|required|file|mimes:jpeg,jpg,png|max:2048',
    //             'synopsis' => 'required|string',
    //             'status' => 'required|in:completed,ongoing,hiatus',
    //             'genres' => 'nullable|array', // Multiple genres
    //         ]);

    //         // Handle cover image upload if provided
    //         $coverImageUrl = $novel->cover_image_url;
    //         if ($request->hasFile('cover_image')) {
    //             $file = $request->file('cover_image');
    //             if ($file->getSize() > 2 * 1024 * 1024) {
    //                 throw new \Exception('Image size must be less than 2MB');
    //             }
    //             $timestamp = time();
    //             $fileExt = $file->getClientOriginalExtension();
    //             $fileName = "{$timestamp}.{$fileExt}";
    //             // $coverImageUrl = $file->storeAs('', $fileName, 'novel_covers_v2');
    //             // $coverImageUrl = "novel_covers_v2/{$fileName}";
    // //blackblaze
    // $path = $file->storeAs('novel_covers', $fileName, 's3');
    // $coverImageUrl = Storage::disk('s3')->url($path);
    // // $path = Storage::disk('s3')->putFileAs(
    // //     'novel_covers',
    // //     $file,
    // //     $fileName,
    // //     [
    // //         'visibility' => 'public',
    // //         'ContentType' => $file->getMimeType(), // 👈 explicitly set MIME
    // //     ]
    // // );  









    //             // Delete old image if it exists
    //             if ($novel->cover_image_url) {
    //                 $oldFileName = basename($novel->cover_image_url);
    //                 // Storage::disk('novel_covers_v2')->delete($oldFileName);
    //                 //blaclblaze
    //                 Storage::disk('s3')->delete("novel_covers/{$oldFileName}");

    //             }
    //         }

    //         // Check or create author and update author_id
    //         $author = Author::firstOrCreate(['name' => $data['author']], ['name' => $data['author']]);
    //         $authorId = $author->id;

    //         // Update novel
    //         $novel->update([
    //             'title' => $data['title'],
    //             'author' => $data['author'], // Optional: Keep this if you want to store the name
    //             'author_id' => $authorId,    // Update the relationship
    //             'publisher' => $data['publisher'],
    //             'publishing_year' => $data['publishing_year'],
    //             'cover_image_url' => $coverImageUrl,
    //             'synopsis' => $data['synopsis'],
    //             'status' => $data['status'],
    //         ]);

    //         // Sync multiple genres
    //         if (isset($data['genres']) && is_array($data['genres'])) {
    //             $novel->genres()->sync($data['genres']);
    //         }

    //         // Load relationships for response
    //         $novel->load('genres', 'author');

    //         return new NovelResource($novel);
    //     } catch (\Exception $e) {
    //         Log::error('Failed to update novel', ['id' => $id, 'error' => $e->getMessage()]);
    //         return response()->json(['error' => $e->getMessage()], 400);
    //     }
    // }


public function update(Request $request, Novel $novel)
{
    // Add comprehensive logging
    \Log::info('Update method called', [
        'novel_exists' => $novel ? 'yes' : 'no',
        'novel_id' => $novel ? $novel->id : 'null',
        'request_route_params' => $request->route()->parameters(),
        'request_method' => $request->method(),
        'request_url' => $request->fullUrl()
    ]);

    // Check if novel exists and has ID
    if (!$novel || !$novel->exists) {
        \Log::error('Novel not found or does not exist', [
            'novel' => $novel,
            'route_params' => $request->route()->parameters()
        ]);
        return response()->json(['error' => 'Novel not found'], 404);
    }

    \Log::info('Novel found', ['id' => $novel->id, 'title' => $novel->title]);

    // Validate - ADD STATUS HERE
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'status' => 'required|string|in:draft,published,completed,ongoing', // Add your valid status values
        'cover_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    ]);

    \Log::info('Validation passed', $validated);

    // Keep old cover URL
    $coverImageUrl = $novel->cover_image_url;

    // Handle new upload if present
    if ($request->hasFile('cover_image')) { // Fixed: was 'coverimage'
        \Log::info('File upload detected');
        try {
            $file = $request->file('cover_image'); // Fixed: consistent naming
            $fileName = time() . '' . uniqid() . '.' . $file->getClientOriginalExtension();

            if (config('filesystems.default') === 's3') {
                // S3 / Blackblaze B2 upload
                $path = $file->storeAs('novel_covers', $fileName, 's3');

                if (!$path) {
                    \Log::error('S3 upload failed: empty path returned.');
                } else {
                    $coverImageUrl = Storage::disk('s3')->url($path);
                    \Log::info('S3 upload successful', ['url' => $coverImageUrl]);
                }
            } else {
                // Local storage
                $path = $file->storeAs('novel_covers', $fileName, 'public');
                $coverImageUrl = asset('storage/' . $path);
                \Log::info('Local upload successful', ['url' => $coverImageUrl]);
            }
        } catch (\Exception $e) {
            \Log::error('Cover image upload failed: ' . $e->getMessage());
            // Keep old URL if upload fails
        }
    }

    \Log::info('About to update novel', [
        'novel_id' => $novel->id,
        'update_data' => [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? $novel->description,
            'status' => $validated['status'], // ADD THIS
            'cover_image_url' => $coverImageUrl,
        ]
    ]);

    // Update novel - ADD STATUS HERE
    $novel->update([
        'title' => $validated['title'],
        'description' => $validated['description'] ?? $novel->description,
        'status' => $validated['status'], // ADD THIS LINE
        'cover_image_url' => $coverImageUrl,
    ]);

    \Log::info('Novel updated successfully', ['novel_id' => $novel->id]);

    // Refresh the model to ensure we have the latest data
    $novel->refresh();

    \Log::info('About to redirect', [
        'novel_id' => $novel->id,
        'route_name' => 'novels.show'
    ]);

    try {
        // Use the ID directly since your route expects {id} parameter
        $redirectUrl = redirect()->route('novels.show', ['id' => $novel->id])
            ->with('success', 'Novel updated successfully!');

        \Log::info('Redirect URL generated successfully');
        return $redirectUrl;

    } catch (\Exception $e) {
        \Log::error('Redirect failed', [
            'error' => $e->getMessage(),
            'novel_id' => $novel->id
        ]);

        // Fallback response
        return response()->json([
            'success' => true,
            'message' => 'Novel updated successfully!',
            'novel' => $novel
        ]);
    }
}



    /**
     * Fetch all genres, ordered by name.
     */
    public function getGenres()
    {
        try {
            $genres = Genre::orderBy('name')->get();

            return response()->json([
                'data' => $genres->map(function ($genre) {
                    return [
                        'id' => (int) $genre->id,
                        'name' => $genre->name,
                        'slug' => $genre->slug,
                    ];
                })->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch genres', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch genres: ' . $e->getMessage()], 500);
        }
    }






    /**
     * Fetch novel statistics by ID or title (returns 0,0 if no stats exist)
     * 
     * @param string|int $novelIdentifier Novel ID or title
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNovelStatsById($id)
    {
        try {
            // Validate identifier
            if (empty($id)) {
                return response()->json(['error' => 'Novel identifier is required'], 422);
            }

            // Find novel by ID or title
            $novel = is_numeric($id)
                ? Novel::find($id)
                : Novel::where('title', urldecode($id))->first();

            if (!$novel) {
                return response()->json([
                    'error' => 'Novel not found',
                    'searched' => $id,
                    'suggestion' => is_numeric($id)
                        ? 'Check the novel ID'
                        : 'Verify the title spelling and try exact match'
                ], 404);
            }

            // Try to get existing stats
            $stats = NovelStats::where('novel_id', $novel->id)->first();

            // Return default values if no stats exist
            if (!$stats) {
                return response()->json([
                    'data' => [
                        'average_rating' => 0.0,
                        'rating_count' => 0,
                    ],
                ]);
            }

            return response()->json([
                'data' => [
                    'average_rating' => (float) $stats->average_rating,
                    'rating_count' => (int) $stats->rating_count,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch novel stats', [
                'identifier' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Even on error, return default values
            return response()->json([
                'data' => [
                    'average_rating' => 0.0,
                    'rating_count' => 0,
                ],
            ]);
        }
    }

    /**
     * Helper method to fetch or calculate novel stats.
     *
     * @param  int  $novelId
     * @return \App\Models\NovelStats
     * @throws \Exception
     */
    private function getNovelStats($novelId)
    {
        $stats = NovelStats::where('novel_id', $novelId)->first();

        return $stats ?: $this->createDefaultStats($novelId);
    }

    private function createDefaultStats($novelId)
    {
        // Get novel for title if exists
        $novel = Novel::find($novelId);
        $title = $novel ? $novel->title : '';

        // Calculate actual ratings if they exist
        $ratings = NovelRating::where('novel_id', $novelId)->get();
        $ratingCount = $ratings->count();
        $averageRating = $ratingCount > 0 ? $ratings->avg('rating') : 0;

        // Calculate actual chapter count
        $chapterCount = Chapter::where('novel_id', $novelId)->count();

        return new NovelStats([
            'novel_id' => $novelId,
            'title' => $title,
            'chapter_count' => $chapterCount,
            'reader_count' => 0,
            'average_rating' => $averageRating,
            'rating_count' => $ratingCount,
            'total_views' => 0,
            'last_updated' => now(),
        ]);
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
                // Storage::disk('novel_covers_v2')->delete($fileName);
                //blaclblaze
                Storage::disk('s3')->delete("novel_covers/{$fileName}");

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
            // Return all novels if no query
            $novels = Novel::with('genres', 'author')
                ->orderBy('created_at', 'desc')
                ->get();
            return new NovelCollection($novels);
        }

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