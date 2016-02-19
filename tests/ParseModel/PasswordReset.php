<?php namespace App;

use App\Http\ParseModel\ParseModel;
use Illuminate\Database\Eloquent\Model;


/**
 * App\Test
 *
 */
class PasswordReset extends ParseModel  {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'password_resets';

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $fillable = ['email', 'token'];

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = array('password', 'remember_token');

}
