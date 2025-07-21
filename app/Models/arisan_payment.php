<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class arisan_payment extends Model
{
    protected $guarded = [];

    public function group()
    {
        return $this->belongsTo(arisan_group::class, 'group_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
