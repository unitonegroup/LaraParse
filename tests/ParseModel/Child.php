<?php

namespace App\ParseModel;
use App\Http\ParseModel\ParseModel;
use Illuminate\Database\Eloquent\Model;

class Child extends ParseModel
{
    public $table = "Child";
    protected $fillable = ['x','foo'];
}
