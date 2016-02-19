<?php namespace App;

use App\Http\ParseModel\ParseModel;
use Illuminate\Database\Eloquent\Model;


/**
 * App\Test
 *
 */
class Object extends ParseModel  {

    protected $primaryKey = 'objectId';
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $table = 'BoxedNumber';

	protected $fillable = ['number','x'];
}
