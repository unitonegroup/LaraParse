<?php namespace App;

use App\Http\ParseModel\ParseModel;
use Illuminate\Database\Eloquent\Model;


/**
 * App\Test
 *
 */
class SimpleObject extends ParseModel  {

    protected $primaryKey = 'objectId';
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $table = 'SimpleObject';

	protected $fillable = ['foo','bar','test','myString','x','y','child_id'];

    public function child()
    {
        return $this->belongsTo(Child::class,'child_id');
    }
}
