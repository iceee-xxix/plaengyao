<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\BooksenderController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\UsersController;
use Egulias\EmailValidator\EmailValidator;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('login');
});
Route::post('/login/auth', [LoginController::class, 'auth'])->name('login.auth');
Route::get('/login/logout', [LoginController::class, 'logout'])->name('login.logout');

Route::middleware('auth.admin')->group(function () {
    Route::get('/book', [BookController::class, 'index'])->name('book.index');
    Route::get('/book/getEmail', [BookController::class, 'getEmail'])->name('book.getEmail');
    Route::get('/book/show', [BookController::class, 'show'])->name('book.show');
    Route::post('/book/bookType', [BookController::class, 'bookType'])->name('book.bookType');
    Route::post('/book/save', [BookController::class, 'save'])->name('book.save');
    Route::post('/book/dataListSearch', [BookController::class, 'dataListSearch'])->name('book.dataListSearch');
    Route::post('/book/dataList', [BookController::class, 'dataList'])->name('book.dataList');
    Route::post('/book/save_stamp', [BookController::class, 'save_stamp'])->name('book.save_stamp');
    Route::post('/book/send_to_admin', [BookController::class, 'send_to_admin'])->name('book.send_to_admin');
    Route::post('/book/admin_stamp', [BookController::class, 'admin_stamp'])->name('book.admin_stamp');
    Route::post('/book/checkbox_send', [BookController::class, 'checkbox_send'])->name('book.checkbox_send');
    Route::post('/book/send_to_save', [BookController::class, 'send_to_save'])->name('book.send_to_save');
    Route::post('/book/confirm_signature', [BookController::class, 'confirm_signature'])->name('book.confirm_signature');
    Route::post('/book/signature_stamp', [BookController::class, 'signature_stamp'])->name('book.signature_stamp');
    Route::post('/book/manager_stamp', [BookController::class, 'manager_stamp'])->name('book.manager_stamp');
    Route::post('/book/uploadPdf', [BookController::class, 'uploadPdf'])->name('bookSender.uploadPdf');
    Route::post('/book/number_save', [BookController::class, 'number_save'])->name('bookSender.number_save');

    Route::get('/users/listUsers', [UsersController::class, 'listUsers'])->name('users.listUsers');
    Route::get('/users/listData', [UsersController::class, 'listData'])->name('users.listData');
    Route::get('/users/edit/{id}', [UsersController::class, 'edit'])->name('users.edit');
    Route::post('/users/save', [UsersController::class, 'save'])->name('users.save');

    Route::get('/tracking', [TrackController::class, 'index'])->name('tracking.index');
    Route::get('/tracking/detail/{id}', [TrackController::class, 'detail'])->name('tracking.detail');
    Route::post('/tracking/dataReportMain', [TrackController::class, 'dataReportMain'])->name('tracking.dataReportMain');
    Route::post('/tracking/dataReportDetail', [TrackController::class, 'dataReportDetail'])->name('tracking.dataReportDetail');
    Route::post('/tracking/getDetailAll', [TrackController::class, 'getDetailAll'])->name('tracking.getDetailAll');

    Route::get('/bookSender', [BooksenderController::class, 'index'])->name('bookSender.index');
    Route::post('/bookSender/bookType', [BooksenderController::class, 'bookType'])->name('bookSender.bookType');
    Route::post('/bookSender/getPosition', [BooksenderController::class, 'getPosition'])->name('bookSender.getPosition');
    Route::post('/bookSender/save', [BooksenderController::class, 'save'])->name('bookSender.save');
});

Route::get('/email', [EmailController::class, 'index'])->name('email.index');

require __DIR__ . '/auth.php';
