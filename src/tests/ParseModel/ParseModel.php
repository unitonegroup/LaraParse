<?php namespace App\ParseModel;

use Illuminate\Database\Eloquent\Model;

/**
 * App\User
 *
 */
class ParseModel extends Model
{
    /**
     * @var array
     */
    public  $many_to_many_relation = array();

    /**
     * @var array
     */
    public $one_to_one_or_many_relation = array();

    /**
	 * The attributes override from model
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes override from model
     *
     * @var string
     */
    const CREATED_AT = 'createdAt';

    /**
     * The attributes override from model
     *
     * @var string
     */
    const UPDATED_AT = 'updatedAt';


    /**
     * @var array
     */
    public $bytesColumns = array();
}