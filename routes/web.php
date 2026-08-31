<?php

use App\Http\Controllers\Chat\ChatController;
use App\Http\Controllers\Documents\DocumentController;
use App\Http\Controllers\Documents\SignatureController;
use App\Http\Controllers\Users\UserController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('documents/{document}/sign', [SignatureController::class, 'store'])->name('documents.sign');
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

    Route::get('chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('chat/conversations', [ChatController::class, 'store'])->name('chat.conversations.store');
    Route::get('chat/conversations/{conversation}', [ChatController::class, 'show'])->name('chat.conversations.show');
    Route::post('chat/conversations/{conversation}/messages', [ChatController::class, 'message'])->name('chat.conversations.messages');
});

Route::middleware(['auth', 'verified', 'administrator'])->group(function () {
    Route::get('admin/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('admin/users', [UserController::class, 'store'])->name('users.store');

    Route::get('admin/documents/create', [DocumentController::class, 'create'])->name('documents.create');
    Route::post('admin/documents', [DocumentController::class, 'store'])->name('documents.store');
});

require __DIR__.'/settings.php';
