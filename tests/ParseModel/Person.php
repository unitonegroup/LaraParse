<?php

namespace App;

use App\Http\ParseModel\ParseModel;
use Illuminate\Database\Eloquent\Model;

class Person extends ParseModel
{
    public $table = "Person";
    protected $fillable = ['name','hometown'];
}
