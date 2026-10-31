<?php

use App\Controllers\MainController;
use App\Controllers\StudyController;
use App\Controllers\TagController;
use App\Controllers\VocabController;
use PXP\Auth\Controllers\LoginController;
use PXP\Auth\Controllers\VerificationController;
use PXP\Auth\Middleware\InteractiveAuth;
use PXP\Auth\Middleware\VerifiedEmail;
use PXP\Http\Controllers\AssetController;
use PXP\Router\Route;

Route::get('/')->do(MainController::class, 'index')->name('main');

Route::group(
    Route::get('/vocabs')->do(VocabController::class, 'index')->name('vocabs'),
    Route::get('/vocabs/create')->do(VocabController::class, 'create')->name('vocabs.create'),
    Route::post('/vocabs')->do(VocabController::class, 'store'),

    Route::get('/tags')->do(TagController::class, 'index')->name('tags'),
    Route::get('/tags/{tagId}')->do(TagController::class, 'show')->name('tags.show'),
    Route::post('/tags/{tagId}/share/{recipientId}')->do(TagController::class, 'share')->name('tags.share'),

    Route::get('/study')->do(StudyController::class, 'index')->name('study'),
    Route::get('/study/{progress}/study')->do(StudyController::class, 'study')->name('study.study'),
    Route::get('/study/{progress}/check')->do(StudyController::class, 'check')->name('study.check'),
    Route::get('/study/{progress}/bury')->do(StudyController::class, 'bury')->name('study.bury'),
    Route::get('/study/{progress}/result/{result}')->do(StudyController::class, 'result')->name('study.result'),
)
    ->middleware(InteractiveAuth::class)
    ->middleware(VerifiedEmail::class);

// Auth

Route::group(
    Route::get('/auth/verify')->do(VerificationController::class, 'verify')->name('verify'),

    Route::get('/auth/login')->do(LoginController::class, 'form')->name('login'),
    Route::post('/auth/login')->do(LoginController::class, 'login'),
);

Route::group(
    Route::get('/auth/logout')->do(LoginController::class, 'logout')->name('logout'),
    Route::post('/auth/logout')->do(LoginController::class, 'logout'),
)
    ->middleware(InteractiveAuth::class);

// Assets

Route::group(
    Route::get('/css/{file}')->do(AssetController::class, 'css')->name('css'),
);
