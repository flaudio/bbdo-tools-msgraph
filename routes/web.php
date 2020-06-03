<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/', 'HomeController@welcome');

Route::get('/auth/ms-graph/signin', 'MSGraph\AuthController@signin')->name('auth.ms-graph.signin');
Route::get('/auth/ms-graph/callback', 'MSGraph\AuthController@callback');
Route::get('/signout', 'MSGraph\AuthController@signout');
