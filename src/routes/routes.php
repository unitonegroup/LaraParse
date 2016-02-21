<?php

/*
 * Object Routes based on Parse Rest API documentation
 * https://parse.com/docs/rest/guide#quick-reference-objects
*/
Route::group(['prefix' => '/1/'], function () {

    Route::group(['prefix' => 'classes/'], function () {
        Route::post('{className}', 'UnitOneICT\LaraParse\ParseObjectController@create');
        Route::get('{className}/{objectId}', 'UnitOneICT\LaraParse\ParseObjectController@getById');
        Route::put('{className}/{objectId}', 'UnitOneICT\LaraParse\ParseObjectController@update');
        Route::get('{className}', 'UnitOneICT\LaraParse\ParseObjectController@get');
        Route::delete('{className}/{objectId}', 'UnitOneICT\LaraParse\ParseObjectController@delete');
    });

    Route::post('batch', 'UnitOneICT\LaraParse\ParseObjectController@batch');

});
