<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    public function federation() { return $this->belongsTo(Federation::class); }
    public function churches() { return $this->hasMany(Church::class); }
}
