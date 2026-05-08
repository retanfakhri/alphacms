<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SettingAsset extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'setting_assets';

    protected $fillable = ['group'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo_light')->singleFile()->useDisk('site_settings_photo');
        $this->addMediaCollection('logo_dark')->singleFile()->useDisk('site_settings_photo');
        $this->addMediaCollection('favicon')->singleFile()->useDisk('site_settings_photo');
        $this->addMediaCollection('watermark_image')->singleFile()->useDisk('site_settings_photo');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->format('webp')
            ->quality(90)
            ->nonQueued()
            ->performOnCollections('logo_light', 'logo_dark', 'watermark_image');
    }

    public function getLogoLightUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('logo_light');
        if (! $media) {
            return null;
        }

        return str_ends_with($media->file_name, '.svg')
            ? $media->getUrl()
            : ($media->getUrl('webp') ?: $media->getUrl());
    }

    public function getLogoDarkUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('logo_dark');
        if (! $media) {
            return null;
        }

        return str_ends_with($media->file_name, '.svg')
            ? $media->getUrl()
            : ($media->getUrl('webp') ?: $media->getUrl());
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('favicon') ?: null;
    }

    public function getWatermarkImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('watermark_image');
        if (! $media) {
            return null;
        }

        return str_ends_with($media->file_name, '.svg')
            ? $media->getUrl()
            : ($media->getUrl('webp') ?: $media->getUrl());
    }
}
