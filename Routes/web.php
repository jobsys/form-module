<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix("/manager/form")->name("page.manager.form.")->group(function () {
	Route::get('/index', [\Modules\Form\Http\Controllers\FormController::class, 'pageIndex'])->name('index');
	Route::get('/design/{slug}', [\Modules\Form\Http\Controllers\FormController::class, 'pageDesign'])->name('design');
});
