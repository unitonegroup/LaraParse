<?php

namespace App;

use App\Http\ParseModel\ParseModel;
use Illuminate\Database\Eloquent\Model;

class PParent extends ParseModel
{
    public $table = "pparent";
    protected $fillable = ['x','y','child_id','foofoo_id','barbar_id','foo','num'];

    public function child( ){
        return $this->belongsTo(Child::class);
    }

    public function foofoo( ){
        return $this->belongsTo(Foo::class);
    }

    public function barbar( ){
        return $this->belongsTo(Bar::class);
    }


}
