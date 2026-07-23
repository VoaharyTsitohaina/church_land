<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Override;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Property extends Model implements HasMedia
{
  use InteractsWithMedia;
    protected $fillable = ['reference','name','property_type_id','church_id','region',
  'admin_district','commune','fokontany','address','latitude','longitude','area',
  'land_title_number','cadastral_number','legal_status','acquisition_mode',
  'acquisition_date','estimated_value','current_use','observations','history','created_by'];

public function church() { return $this->belongsTo(Church::class); }
public function type() { return $this->belongsTo(PropertyType::class, 'property_type_id'); }
#[Override]
	public function registerMediaCollections(): void
  {
    $this->addMediaCollection('titre_foncier')->singleFile();
    $this->addMediaCollection('plan')->singleFile();
    $this->addMediaCollection('acte')->singleFile();
    $this->addMediaCollection('photos');
    $this->addMediaCollection('autres');
  }
}
