<?php namespace App\ParseModel;

/**
 * App\Test
 *
 */
class TestObject extends ParseModel  {

    protected $table = 'testobject';

    public $bytesColumns = array(
        'byteColumn'
    );
	protected $fillable = ['user_id','name','byteColumn','randomkeyagain','randomkey','yo','f8','time','number','a','foo','bar','test','myString','x','y','num','awesome','great','yes','no','dog','cat','e_id', 'from_id', 'to_id'];
}
