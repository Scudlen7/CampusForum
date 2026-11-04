<?php

use App\Http\Controllers\Admin\BannerAdminController;
use App\Http\Controllers\Admin\CategoryAdminController;
use App\Http\Controllers\Admin\CertificationAdminController;
use App\Http\Controllers\Admin\CommentAdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PostAdminController;
use App\Http\Controllers\Admin\ReportAdminController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Api\CommentApiController;
use App\Http\Controllers\Api\LikeApiController;
use App\Http\Controllers\Api\MetaApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\PostApiController;
use App\Http\Controllers\Api\ReportApiController;
use App\Http\Controllers\Api\UploadApiController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Forum\PostController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationPageController;
use App\Http\Controllers\User\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::get('/forum', [PostController::class, 'forumIndex'])->name('forum.index');
Route::get('/forum/{slug}', [PostController::class, 'forumIndex'])->name('forum.category');

Route::middleware('auth')->group(function () {
    Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
    Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');

    Route::get('/notifications', [NotificationPageController::class, 'index'])->name('notifications.index');

    Route::get('/user/settings', [ProfileController::class, 'settings'])->name('user.settings');
    Route::post('/user/settings', [ProfileController::class, 'updateSettings']);
    Route::get('/user/certification', [ProfileController::class, 'certification'])->name('user.certification');
    Route::post('/user/certification', [ProfileController::class, 'submitCertification']);
});

Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');

Route::get('/user/{username}', [ProfileController::class, 'show'])->name('user.profile');

Route::prefix('api')->group(function () {
    Route::get('/meta/categories', [MetaApiController::class, 'categories']);
    Route::get('/meta/tags', [MetaApiController::class, 'tags']);
    Route::get('/meta/banners', [MetaApiController::class, 'banners']);
    Route::get('/posts', [PostApiController::class, 'index']);
    Route::get('/posts/{post}/comments', [CommentApiController::class, 'index']);

    Route::middleware('auth')->group(function () {
        Route::post('/posts', [PostApiController::class, 'store']);
        Route::put('/posts/{post}', [PostApiController::class, 'update']);
        Route::post('/posts/{post}/comments', [CommentApiController::class, 'store']);
        Route::post('/likes/toggle', [LikeApiController::class, 'toggle']);
        Route::post('/reports', [ReportApiController::class, 'store']);
        Route::get('/notifications', [NotificationApiController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationApiController::class, 'unreadCount']);
        Route::post('/notifications/mark-read', [NotificationApiController::class, 'markRead']);
        Route::post('/upload/image', [UploadApiController::class, 'image']);
    });
});

Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/users', [UserAdminController::class, 'index'])->name('admin.users');
    Route::get('/certifications', [CertificationAdminController::class, 'index'])->name('admin.certifications');
    Route::get('/posts', [PostAdminController::class, 'index'])->name('admin.posts');
    Route::get('/comments', [CommentAdminController::class, 'index'])->name('admin.comments');
    Route::get('/reports', [ReportAdminController::class, 'index'])->name('admin.reports');
    Route::get('/categories', [CategoryAdminController::class, 'index'])->name('admin.categories');
    Route::get('/banners', [BannerAdminController::class, 'index'])->name('admin.banners');

    Route::prefix('api')->group(function () {
        Route::get('/users', [UserAdminController::class, 'apiIndex']);
        Route::post('/users/{user}/ban', [UserAdminController::class, 'ban']);
        Route::post('/users/{user}/unban', [UserAdminController::class, 'unban']);
        Route::get('/certifications', [CertificationAdminController::class, 'apiIndex']);
        Route::get('/certifications/{certification}/image', [CertificationAdminController::class, 'image']);
        Route::post('/certifications/{certification}/approve', [CertificationAdminController::class, 'approve']);
        Route::post('/certifications/{certification}/reject', [CertificationAdminController::class, 'reject']);
        Route::get('/posts', [PostAdminController::class, 'apiIndex']);
        Route::post('/posts/{post}/toggle-hidden', [PostAdminController::class, 'toggleHidden']);
        Route::post('/posts/{post}/toggle-pinned', [PostAdminController::class, 'togglePinned']);
        Route::delete('/posts/{post}', [PostAdminController::class, 'destroy']);
        Route::get('/comments', [CommentAdminController::class, 'apiIndex']);
        Route::post('/comments/{comment}/toggle-hidden', [CommentAdminController::class, 'toggleHidden']);
        Route::delete('/comments/{comment}', [CommentAdminController::class, 'destroy']);
        Route::get('/reports', [ReportAdminController::class, 'apiIndex']);
        Route::get('/reports/{report}', [ReportAdminController::class, 'show']);
        Route::post('/reports/{report}/handle', [ReportAdminController::class, 'handle']);
        Route::get('/categories', [CategoryAdminController::class, 'apiIndex']);
        Route::post('/categories', [CategoryAdminController::class, 'store']);
        Route::put('/categories/{category}', [CategoryAdminController::class, 'update']);
        Route::get('/banners', [BannerAdminController::class, 'apiIndex']);
        Route::post('/banners', [BannerAdminController::class, 'store']);
        Route::put('/banners/{banner}', [BannerAdminController::class, 'update']);
    });
});
