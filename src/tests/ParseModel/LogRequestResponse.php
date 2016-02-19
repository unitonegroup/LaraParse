<?php
/**
 * Created by PhpStorm.
 * User: Mohammed-Skaik
 * Date: 8/2/2015
 * Time: 8:45 AM
 */

namespace App\ParseModel;



class LogRequestResponse extends ParseModel
{
    protected $table = 'log';

    protected $fillable = array('request', 'response', 'method', 'method_data', 'user_agent', 'status', 'exception');
}