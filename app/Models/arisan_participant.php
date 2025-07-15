<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class arisan_participant extends Model
{
    protected $guarded = [];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function arisanGroup(){
        return $this->belongsTo(arisan_group::class);
    }
}
