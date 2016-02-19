<?php

namespace App;

use App\Http\ParseModel\ParseModel;
use Illuminate\Database\Eloquent\Model;

class Sito extends ParseModel
{
    public $table = "Sito";
    protected $fillable = ['testKey','randomKey','testChildKey'];



    public function testChildKey()
    {
        return $this->belongsTo(Child::class);
    }
}
