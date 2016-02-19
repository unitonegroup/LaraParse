<?php

namespace App\ParseModel;
use App\Http\ParseModel\ParseModel;
use Illuminate\Database\Eloquent\Model;

class Bar extends ParseModel
{
    public $table = "Bar";
    protected $fillable = ['rev'];

}
