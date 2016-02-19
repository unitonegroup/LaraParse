<?php

/*
 * Object Routes based on Parse Rest API documentation
 * https://parse.com/docs/rest/guide#quick-reference-objects
*/
Route::group(['prefix' => '/1/'], function () {

    Route::group(['prefix' => 'classes/'], function () {
        Route::post('{className}', 'Parse\ParseObjectController@create');
        Route::get('{className}/{objectId}', 'Parse\ParseObjectController@getById');
        Route::put('{className}/{objectId}', 'Parse\ParseObjectController@update');
        Route::get('{className}', 'Parse\ParseObjectController@get');
        Route::delete('{className}/{objectId}', 'Parse\ParseObjectController@delete');
    });
    Route::post('batch', 'Parse\ParseObjectController@batch');

});
