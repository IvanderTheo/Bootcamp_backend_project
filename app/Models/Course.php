<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Module;
use App\Models\CourseCategory;

class Course extends Model
{
    /** @use HasFactory<\Database\Factories\CourseFactory> */
    use HasFactory;
    protected $fillable = ['id_user', 'name', 'description', 'price', 'quota'];

    public function instructor()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function modules()
    {
        return $this->hasMany(Module::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }
    public function category()
    {
        return $this->belongsTo(CourseCategory::class, 'category_id');
    }
}
