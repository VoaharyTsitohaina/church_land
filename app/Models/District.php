<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $fillable = ['name', 'federation_id'];
    
    public function federation() { return $this->belongsTo(Federation::class); }
    public function churches() { return $this->hasMany(Church::class); }
}
