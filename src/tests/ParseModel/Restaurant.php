<?php

namespace App;

use App\Http\ParseModel\ParseModel;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends ParseModel
{
    public $table = "Restaurant";
    protected $fillable = ['ratings','location'];
}
