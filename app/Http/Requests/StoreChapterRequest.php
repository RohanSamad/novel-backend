<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChapterRequest extends FormRequest
{
    public function authorize()
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        $profile = $user->profile;
        if (!$profile) {
            return false;
        }
        return $profile->role === 'admin';
    }

    public function rules()
    {
        return [
            'novel_id' => 'required|integer|exists:novels,id',
            'chapter_number' => 'required|integer|min:1',
            'title' => 'required|string|max:255',
            'audio_file' => 'nullable|file|mimes:mp3,wav,opus|max:50240', // 50MB max
            'content_text' => 'required|string|min:1',
            'order_index' => 'required|integer|min:1',
        ];
    }
}