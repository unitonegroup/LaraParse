<?php namespace App\ParseModel;

use App\Http\ParseModel\ParseModel;
use Illuminate\Database\Eloquent\Model;


/**
 * App\Test
 *
 */
class Container extends ParseModel  {

    protected $primaryKey = 'objectId';
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $table = 'Container';

	protected $fillable = ['item_id'];

    public function item()
    {
        return $this->belongsTo(Item::class,'item_id');
    }

  //  public $one_to_one_or_many_relation = array('item_id');
}
