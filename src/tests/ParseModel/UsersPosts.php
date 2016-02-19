<?php namespace App;

use App\Http\ParseModel\ParseModel;
use Illuminate\Database\Eloquent\Model;

/**
 * Created by PhpStorm.
 * User: Mohammed-Skaik
 * Date: 7/22/2015
 * Time: 11:49 AM
 */
class UsersPosts extends ParseModel  {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users_posts';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $fillable = ['users_id', 'post_id', 'createdAt', 'updatedAt'];

    protected $timestamps = true;
}
