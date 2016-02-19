<?php namespace App\ParseModel;

use Illuminate\Database\Eloquent\Model;


/**
 * App\Test
 *
 */
class Item extends ParseModel  {

    protected $primaryKey = 'objectId';
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $table = 'item';

	protected $fillable = ['item','property','foo','e'];
}
