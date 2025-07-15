<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class arisan_draw extends Model
{
    protected $guarded = [];

    public function user(){
        return $this->belongsTo(User::class);
    }
}
