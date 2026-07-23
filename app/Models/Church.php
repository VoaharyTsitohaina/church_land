<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Church extends Model
{
    protected $fillable = ['name', 'district_id'];

    public function district() { return $this->belongsTo(District::class); }
    public function properties() { return $this->hasMany(Property::class); }
}
