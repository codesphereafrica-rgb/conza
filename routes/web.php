<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/inscription', [AuthController::class, 'showRegister'])->name('register');
Route::post('/inscription', [AuthController::class, 'register']);
Route::get('/connexion', [AuthController::class, 'showLogin'])->name('login');
Route::post('/connexion', [AuthController::class, 'login']);
Route::get('/mot-de-passe-oublie', [PasswordResetController::class, 'requestForm'])->name('password.request');
Route::post('/mot-de-passe-oublie', [PasswordResetController::class, 'sendLink'])->name('password.email');
Route::get('/reinitialiser-mot-de-passe/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
Route::post('/reinitialiser-mot-de-passe', [PasswordResetController::class, 'reset'])->name('password.update');
Route::post('/deconnexion', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/forum', [ForumController::class, 'index'])->name('forum.index');
Route::get('/forum/categorie/{slug}', [ForumController::class, 'category'])->name('forum.category');
Route::get('/forum/sujet/{id}', [ForumController::class, 'topic'])->name('forum.topic');
Route::get('/recherche', [ForumController::class, 'search'])->name('forum.search');

Route::middleware('auth')->group(function () {
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::middleware(\App\Http\Middleware\EnsureIsAdmin::class)->group(function () {
        Route::get('/forum/nouveau-sujet', [ForumController::class, 'createTopic'])->name('forum.create-topic');
        Route::post('/forum/nouveau-sujet', [ForumController::class, 'storeTopic']);
    });

    Route::delete('/forum/sujet/{topic}', [ForumController::class, 'destroyTopic'])->name('forum.topic.destroy');
    Route::post('/forum/sujet/{topic}/repondre', [ForumController::class, 'storeReply'])->name('forum.reply');
    Route::post('/forum/reaction/{post}', [ForumController::class, 'react'])->name('forum.react');
    Route::post('/account/delete', [AuthController::class, 'deleteAccount'])->name('account.delete');
});

Route::get('/dons', [DonationController::class, 'index'])->name('donations.index');
Route::match(['get', 'post'], '/callback', [DonationController::class, 'mobileCallback'])->name('payment.callback');
Route::match(['get', 'post'], '/dons/callback/{reference}', [DonationController::class, 'callback'])->name('donations.callback');
Route::post('/dons', [DonationController::class, 'store'])->middleware('auth')->name('donations.store');

Route::middleware(['auth', \App\Http\Middleware\EnsureIsAdmin::class])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.index');
    Route::get('/categories', [AdminController::class, 'categories'])->name('admin.categories');
    Route::post('/categories', [AdminController::class, 'storeCategory'])->name('admin.categories.store');
    Route::post('/goal', [AdminController::class, 'updateGoal'])->name('admin.goal.update');
    Route::post('/donations/{donation}/mark-paid', [\App\Http\Controllers\DonationController::class, 'markPaid'])->name('admin.donations.markPaid');
    Route::post('/donations/{donation}/mark-pending', [\App\Http\Controllers\DonationController::class, 'markPending'])->name('admin.donations.markPending');
    Route::post('/reset/goal', [AdminController::class, 'resetGoal'])->name('admin.reset.goal');
    Route::post('/reset/paid', [AdminController::class, 'resetPaidAmounts'])->name('admin.reset.paid');
    Route::get('/donations/archive', [AdminController::class, 'donationsArchive'])->name('admin.donations.archive');
    Route::post('/donations/archive/{id}/restore', [AdminController::class, 'restoreArchivedDonation'])->name('admin.donations.restore');
    Route::get('/users', [AdminController::class, 'usersList'])->name('admin.users');
    Route::post('/users/{user}/make-admin', [AdminController::class, 'makeAdmin'])->name('admin.users.makeAdmin')->middleware(\App\Http\Middleware\EnsureSuperAdmin::class);
    Route::post('/users/{user}/update-role', [AdminController::class, 'updateRole'])->name('admin.users.updateRole')->middleware(\App\Http\Middleware\EnsureSuperAdmin::class);
    Route::post('/users/{user}/toggle-block', [AdminController::class, 'toggleBlock'])->name('admin.users.toggleBlock');
    Route::post('/users/{user}/delete', [AdminController::class, 'deleteUser'])->name('admin.users.delete');
});
