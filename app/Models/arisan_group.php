<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class arisan_group extends Model
{
    protected $guarded = [];

   public function participants()
    {
        return $this->hasMany(arisan_participant::class, 'group_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'arisan_participants', 'group_id', 'user_id');
    }



    
}
