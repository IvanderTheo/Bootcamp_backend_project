<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Module;

class Lesson extends Model
{
    /** @use HasFactory<\Database\Factories\LessonFactory> */
    use HasFactory;

    protected $fillable = [
        'module_id', 'title', 'content', 'video_url', 'order_number'
    ];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }
}
