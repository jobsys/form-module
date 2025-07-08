<?php

use Illuminate\Http\Request;
use Modules\Form\Http\Controllers\FormController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix("manager/form")->name("api.manager.form.")->group(function () {
	Route::get('/form', [FormController::class, 'items'])->name('items');
	Route::get('/form/{id}', [FormController::class, 'item'])->where('id', '[0-9]+')->name('item');
	Route::post('/form', [FormController::class, 'edit'])->name('edit');
	Route::post('/form/delete', [FormController::class, 'delete'])->name('delete');
	Route::post('/form/form-item', [FormController::class, 'formItemEdit'])->name('form-item.edit');
});
