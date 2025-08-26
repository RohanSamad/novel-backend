

<?php

use App\Http\Controllers\API\NovelRatingController;
use App\Http\Controllers\API\UserAuthController;
use App\Http\Controllers\API\AuthorController;
use App\Http\Controllers\API\ChapterController;
use App\Http\Controllers\API\FeaturedNovelController;
use App\Http\Controllers\API\NovelController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [UserAuthController::class, 'login']);
Route::post('/register', [UserAuthController::class, 'register']);

 Route::prefix('authors')->group(function () {
     Route::get('/', [AuthorController::class, 'index'])->name('authors.index');
     Route::get('/{id}', [AuthorController::class, 'show'])->name('authors.show');
    });


    Route::prefix('chapters')->group(function () {
        Route::get('/novel/{novelId}', [ChapterController::class, 'index'])->name('chapters.index');
        //   Route::get('/novel/{novelIdentifier}/{chapterIdentifier}', [ChapterController::class, 'show'])
        // ->where([ 'novelIdentifier' => '.*', 'chapterIdentifier' => '.*'])->name('chapters.show');
        Route::get('/novel/{novelId}/{chapterId}', [ChapterController::class, 'show'])->name('chapters.show');
        Route::get('/recent', [ChapterController::class, 'recent'])->name('chapters.recent');
    });

      Route::prefix('novels')->group(function () {
        Route::get('/stats/{id}', [NovelController::class, 'getNovelStatsById'])->name('novels.stats');
        Route::get('/search', [NovelController::class, 'search']);
        Route::get('/genres', [NovelController::class, 'getGenres'])->name('novels.getGenres'); 
        Route::get('/', [NovelController::class, 'index'])->name('novels.index');
       // Route::get('/{identifier}', [NovelController::class, 'show'])->name('novels.show');
        Route::get('/author/{authorId}', [NovelController::class, 'byAuthor'])->name('novels.byAuthor');
        Route::get('/genre/{genreSlug}', [NovelController::class, 'byGenre'])->name('novels.byGenre');
    });

    Route::get('/novels/{id}', [NovelController::class, 'show'])->name('novels.show');
    // Rating routes
    

      Route::prefix('featured-novels')->group(function () {
        Route::get('/', [FeaturedNovelController::class, 'index'])->name('featured-novels.index');
    });

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/novel-ratings', [NovelRatingController::class, 'store'])->name('novel-ratings.store');
    Route::get('/check-session', [UserAuthController::class, 'checkSession']);
    Route::post('/logout', [UserAuthController::class, 'logout']);
    Route::post('/verify-admin', [UserAuthController::class, 'verifyAdmin']); 
  
    // Admin-only routes
    Route::middleware('admin')->group(function () {
        Route::get('/users', [UserAuthController::class, 'fetchUsers']);
        Route::post('/users/role', [UserAuthController::class, 'updateUserRole']);
        Route::post('/delete-users', [UserAuthController::class, 'deleteUser']);
        Route::prefix('chapters')->group(function () {
            Route::post('/', [ChapterController::class, 'store'])->name('chapters.store');
            Route::post('/{id}', [ChapterController::class, 'update'])->name('chapters.update');
            Route::delete('/{id}', [ChapterController::class, 'destroy'])->name('chapters.destroy');
        });
        Route::prefix('novels')->group(function () {
            Route::post('/', [NovelController::class, 'store'])->name('novels.store');
            Route::post('/{novel}', [NovelController::class, 'update'])->name('novels.update'); // Add this
            Route::delete('/{id}', [NovelController::class, 'destroy'])->name('novels.destroy');
            Route::delete('/novels/bulk', [NovelController::class, 'bulkDestroy']);
        });
        Route::prefix('featured-novels')->group(function () {
            Route::post('/', [FeaturedNovelController::class, 'store'])->name('featured-novels.store');
            Route::delete('/{id}', [FeaturedNovelController::class, 'destroy'])->name('featured-novels.destroy');
        });
    });
});
// routes/api.php

// Add this route with your other novel routes
Route::get('/novels/random-completed', [NovelController::class, 'getRandomCompleted']);

// If you already have a novels resource route, make sure this comes BEFORE it:
// Route::get('/novels/random-completed', [NovelController::class, 'getRandomCompleted']);
// Route::resource('novels', NovelController::class);

// OR if you want to group it with other novel routes:
Route::prefix('novels')->group(function () {
    Route::get('/random-completed', [NovelController::class, 'getRandomCompleted']);
    // ... your other novel routes
});