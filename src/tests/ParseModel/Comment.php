<?php namespace App\ParseModel;

use App\Http\ParseModel\ParseModel;
use Illuminate\Database\Eloquent\Model;
use App\Post;

/**
 * App\Comment
 *
 * @property-read \App\Post $parent 
 */
class Comment extends ParseModel  {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'comment';

	/**
	 * The attributes excluded from parseModel
	 *
	 * @var array
	 */
	public $many_to_many_relation = [];

	/**
	 * The attributes excluded from parseModel
	 *
	 * @var array
	 */
	public $one_to_one_or_many_relation = ['user', 'post_id'];

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $fillable = ['post_id', 'content'];

	public function post() {
		return $this->belongsTo("App\Post");
	}

	public function user() {
		return $this->belongsTo("App\User");
	}
}