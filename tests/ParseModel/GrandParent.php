<?php

namespace App\ParseModel;

use App\Http\ParseModel\ParseModel;
use Illuminate\Database\Eloquent\Model;

class GrandParent extends ParseModel
{
    public $table = "GrandParent";
    protected $fillable = ['z','x','y','pparent_id','foo','num'];

    public function pparent( ){
        return $this->belongsTo(PParent::class);
    }

    public function child( ){
        return $this->belongsTo(Child::class);
    }
}
