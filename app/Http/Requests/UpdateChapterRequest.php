<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChapterRequest extends FormRequest
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
            'novel_id' => 'sometimes|integer|exists:novels,id',
            'chapter_number' => 'sometimes|integer|min:1',
            'title' => 'sometimes|string|max:255',
            'audio_file' => 'nullable|file|mimes:mp3,wav|max:10240', // 10MB max
            'content_text' => 'sometimes|string|min:1',
            'order_index' => 'sometimes|integer|min:1',
        ];
    }
}