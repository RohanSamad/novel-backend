<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuthorResource;
use App\Http\Resources\AuthorCollection;
use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthorController extends Controller
{
    public function index()
    {
        try {
            $authors = Author::orderBy('name', 'asc')->get();
            return new AuthorCollection($authors);
        } catch (\Exception $e) {
            Log::error('Failed to fetch authors', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch authors: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            if (!is_numeric($id) || $id <= 0) {
                return response()->json(['error' => 'Invalid author ID'], 422);
            }

            $author = Author::find($id);

            if (!$author) {
                return response()->json(['error' => 'Author not found'], 404);
            }

            return new AuthorResource($author);
        } catch (\Exception $e) {
            Log::error('Failed to fetch author', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch author: ' . $e->getMessage()], 500);
        }
    }
}