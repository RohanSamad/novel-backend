<?php

     namespace App\Http\Requests;

     use Illuminate\Foundation\Http\FormRequest;

     class StoreNovelRequest extends FormRequest
     {
         public function authorize()
         {
        // Check if a user is authenticated and load the profile relationship
        $user = $this->user();
        if (!$user) {
            return false; // No authenticated user
        }

        // Load the profile and check the role
        $profile = $user->profile;
        if (!$profile) {
            return false; // No profile associated with the user
        }

        return $profile->role === 'admin'; // Return true if the role is 'admin'
    }

         public function rules()
         {
             return [
                 'title' => 'required|string|max:255',
                 'author' => 'required|string|max:255',
                 'publisher' => 'required|string|max:255',
                 'cover_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
                 'synopsis' => 'required|string|min:10',
                 'status' => 'required|in:completed,ongoing,hiatus',
                 'publishing_year' => 'required|integer|min:1800|max:' . (date('Y') + 1),
                 'genres' => 'required|array|min:1',
                 'genres.*' => 'exists:genres,id',
             ];
         }
     }