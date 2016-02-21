<?php namespace App;

use Illuminate\Database\Eloquent\Model;


/**
 * App\Test
 *
 */
class Test extends ParseModel  {

    protected $primaryKey = 'objectId';
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $table = 'test';

	protected $fillable = ['email','username','password','age','active', 'active_code','cheatMode'];
}
