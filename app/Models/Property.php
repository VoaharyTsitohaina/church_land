<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Override;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Activitylog\Models\Activity;

class Property extends Model implements HasMedia
{
  use InteractsWithMedia, LogsActivity;
    protected $fillable = ['reference','name','property_type_id','church_id','region',
  'admin_district','commune','fokontany','address','latitude','longitude','area',
  'land_title_number','cadastral_number','legal_status','acquisition_mode',
  'acquisition_date','estimated_value','current_use','observations','history','created_by'];

  public function church() { return $this->belongsTo(Church::class); }
  public function type() { return $this->belongsTo(PropertyType::class, 'property_type_id'); }
  public function creator() { return $this->belongsTo(User::class, 'created_by'); }
  
  #[Override]
	public function registerMediaCollections(): void
  {
    $this->addMediaCollection('titre_foncier')->singleFile();
    $this->addMediaCollection('plan')->singleFile();
    $this->addMediaCollection('acte')->singleFile();
    $this->addMediaCollection('photos');
    $this->addMediaCollection('autres');
  }

  public function getActivitylogOptions(): LogOptions
    {
      return LogOptions::defaults()
        ->logOnly([
          'reference', 'name', 'property_type_id', 'church_id',
          'area', 'land_title_number', 'cadastral_number',
          'legal_status', 'acquisition_mode', 'acquisition_date',
          'estimated_value', 'current_use', 'observations', 'history',
          'region', 'admin_district', 'commune', 'fokontany', 'address',
          'latitude', 'longitude'
        ])
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs()
        ->dontLogIfAttributesChangedOnly(['updated_at'])
        ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
          'created' => "Bien créé : {$this->type->name} - {$this->name} - {$this->church->name} - district {$this->church->district->name} - {$this->church->district->federation->name}",
          'updated' => "Bien modifié : {$this->type->name} - {$this->name} - {$this->church->name} - district {$this->church->district->name} - {$this->church->district->federation->name}",
          'deleted' => "Bien supprimé : {$this->type->name} - {$this->name} - {$this->church->name} - district {$this->church->district->name} - {$this->church->district->federation->name}",
          default => $eventName,
        });
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
      $properties = $activity->properties->toArray();

      foreach (['attributes', 'old'] as $section) {
        if (!isset($properties[$section])) {
            continue;
        }

        if (array_key_exists('property_type_id', $properties[$section])) {
            $id = $properties[$section]['property_type_id'];
            $properties[$section]['type'] = $id
                ? PropertyType::find($id)?->name
                : null;
            unset($properties[$section]['property_type_id']);
        }
        if (array_key_exists('church_id', $properties[$section])) {
            $id = $properties[$section]['church_id'];
            $properties[$section]['church'] = $id
                ? Church::find($id)?->name
                : null;
            unset($properties[$section]['church_id']);
        }
    }

      $activity->properties = $properties;
    }
}
