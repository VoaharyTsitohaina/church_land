<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Federation extends Model
{
    protected $fillable = ['name'];

    public function districts() { return $this->hasMany(District::class); }
}
