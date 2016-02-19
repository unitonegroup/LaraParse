<?php namespace App;

use App\Http\ParseModel\ParseModel;
use Illuminate\Database\Eloquent\Model;


/**
 * App\Test
 *
 */
class TimeObject extends ParseModel
{

    protected $table = 'timeobject';

	protected $fillable = ['user_id','byteColumn','randomkeyagain','randomkey','yo','f8','time','number','a','foo','bar','test','myString','x','y','num','awesome','great','yes','no','dog','cat','e_id'];
}
