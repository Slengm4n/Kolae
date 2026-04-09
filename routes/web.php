<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VenueController;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/dashboard', function () {
    if (auth()->user()->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

//Rotas Admin (Protegida)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    //Rota padrao dashboard admin
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    //Rota gerencimento de usuarios
    Route::get('/users', [AdminController::class, 'index'])->name('users.index');
    //Rota gerenciamento de quadras
    Route::get('/venues', [AdminController::class, 'venues'])->name('venues.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::middleware(['auth'])->group(function () {
    Route::resource('venues', VenueController::class);
});

// Troca de idioma
Route::get('/lang/{locale}', function (string $locale) {
    $allowed = ['pt-br', 'en-us', 'es-es', 'hi-in', 'zh-cn', 'it-it', 'ja-jp'];
    if (in_array($locale, $allowed)) {
        session(['idioma' => $locale]);
        app()->setLocale($locale);
    }
    return redirect()->back();
})->name('lang.switch');

// Newsletter
Route::post('/newsletter', function (\Illuminate\Http\Request $request) {
    $request->validate(['email' => 'required|email']);
    return redirect()->back()->with('success', 'Inscrição realizada!');
})->name('newsletter.subscribe');

require __DIR__ . '/auth.php';
