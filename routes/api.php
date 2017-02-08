<?php

use Illuminate\Http\Request;

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

Route::get('/girls/id/{id}', 'GirlController@getInformation');
Route::get('/girls/id', 'GirlController@getMultipleInformation');
Route::get('/girls/picture/{id}', 'GirlController@getPicture');
Route::get('/girls', 'GirlController@getCollection');
Route::get('/girls/id/{id}/cards', 'GirlController@getCards');
Route::get('/girls/id/{id}/sets', 'GirlController@getSets');

Route::get('/cards/id/{id}', 'CardController@getInformation');
Route::get('/cards/id', 'CardController@getMultipleInformation');
Route::get('/cards/picture/{id}', 'CardController@getPicture');
Route::get('/cards/icon/{id}', 'CardController@getIcon');
Route::get('/cards', 'CardController@getCollection');
Route::get('/cards/id/{id}/girls', 'CardController@getGirls');
Route::get('/cards/id/{id}/set', 'CardController@getSet');

Route::get('/sets/id/{id}', 'SetController@getInformation');
Route::get('/sets/id', 'SetController@getMultipleInformation');
Route::get('/sets/picture/{id}', 'SetController@getPicture');
Route::get('/sets/icon/{id}', 'SetController@getIcon');
Route::get('/sets', 'SetController@getCollection');
Route::get('/sets/id/{id}/girls', 'SetController@getGirls');
Route::get('/sets/id/{id}/cards', 'SetController@getCards');

Route::get('/birthdays/today', 'BirthdayController@getBirthdaysToday');
Route::get('/birthdays/today/exist', 'BirthdayController@getBirthdaysTodayExist');
Route::get('/birthdays/today/wikimessage', 'BirthdayController@getBirthdaysWikiMessage');
Route::get('/birthdays/today/picture', 'BirthdayController@getBirthdaysTodayPicture');
Route::get('/birthdays/today/card', 'BirthdayController@getBirthdaysTodayCard');

Route::get('/random/card', 'RandomController@getRandomCard');