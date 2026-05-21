<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectImage extends Model
{
    protected $fillable = ['project_id', 'image_path'];
    
    protected $appends = ['image_url'];
    // Accessor to get the full URL of the image
    public function getImageUrlAttribute(){
        return asset('storage/'.$this->image_path);
    }
    // العلاقة بين الصورة و المشروع علاقة عديد لواحد
    public function project(){
        return $this->belongsTo(Project::class);
    }
}
