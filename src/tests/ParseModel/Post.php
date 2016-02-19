<?php namespace App;

use App\Http\ParseModel\ParseModel;
use Illuminate\Database\Eloquent\Model;


/**
 * App\Post
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\Comment[] $comments 
 */
class Post extends ParseModel  {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'post';

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $fillable = ['title', 'content'];

	public function comment() {
		return $this->hasMany("App\Comment", "parent_id");
	}

	public function like() {
		return $this->belongsToMany('App\User', 'users_posts')->withPivot('updatedAt', 'createdAt')->withTimestamps();
	}

}