<?php namespace App\ParseModel;

use App\Http\ParseModel\ParseModel;
use Illuminate\Database\Eloquent\Model;


/**
 * App\Test
 *
 */
class BoxedNumber extends ParseModel  {

    protected $primaryKey = 'objectId';
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $table = 'BoxedNumber';

	protected $fillable = ['number','x','string'];
}
