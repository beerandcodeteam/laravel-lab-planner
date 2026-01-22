<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::home')->name('home')->middleware('auth');
Route::livewire('/goal/{goal}', 'pages::goals.index')
    ->name('goals.index')
    ->middleware('auth');

Route::livewire('/diagnosis/{diagnosis}', 'pages::diagnosis.index')
    ->name('diagnosis.index')
    ->middleware('auth');

// Auth Routes
Route::middleware('guest')->group(function () {
    Route::livewire('/login', 'pages::auth.login')->name('login');
    Route::livewire('/register', 'pages::auth.register')->name('register');
});

Route::post('/logout', function () {
    auth()->logout();

    return redirect('/');
})->name('logout');

// Design System
Route::prefix('design-system')->name('design-system.')->group(function () {
    Route::livewire('/', 'pages::design-system.index')->name('index');
    Route::livewire('/colors', 'pages::design-system.colors')->name('colors');
    Route::livewire('/typography', 'pages::design-system.typography')->name('typography');
    Route::livewire('/buttons', 'pages::design-system.buttons')->name('buttons');
    Route::livewire('/modals', 'pages::design-system.modals')->name('modals');
    Route::livewire('/inputs', 'pages::design-system.inputs')->name('inputs');
    Route::livewire('/cards', 'pages::design-system.cards')->name('cards');
    Route::livewire('/alerts', 'pages::design-system.alerts')->name('alerts');
    Route::livewire('/tables', 'pages::design-system.tables')->name('tables');
    Route::livewire('/sections', 'pages::design-system.sections')->name('sections');
});
