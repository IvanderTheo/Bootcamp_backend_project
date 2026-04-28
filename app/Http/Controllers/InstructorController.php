<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InstructorController extends Controller
{
    private const CACHE_TTL = 60; // 60 seconds

    /**
     * Get course count per instructor (Aggregation)
     * GET /api/instructors/course-count
     */
    public function courseCount(Request $request)
    {
        try {
            $cacheKey = 'instructors_course_count';
            
            $instructors = Cache::remember($cacheKey, self::CACHE_TTL, function () {
                return User::select('users.id', 'users.name', 'users.email', 'users.role')
                    ->where('users.role', 'instructor')
                    ->where('users.is_active', true)
                    ->withCount('courses')
                    ->with([
                        'courses' => function ($query) {
                            $query->select('id', 'id_user');
                        }
                    ])
                    ->orderByDesc('courses_count')
                    ->get()
                    ->map(function ($instructor) {
                        // Count total students enrolled in instructor's courses
                        $totalStudents = DB::table('enrollments')
                            ->join('courses', 'enrollments.course_id', '=', 'courses.id')
                            ->where('courses.id_user', $instructor->id)
                            ->distinct('enrollments.user_id')
                            ->count('enrollments.user_id');

                        return [
                            'id' => $instructor->id,
                            'name' => $instructor->name,
                            'email' => $instructor->email,
                            'course_count' => $instructor->courses_count,
                            'total_students_enrolled' => $totalStudents,
                        ];
                    });
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Data instructor course count berhasil diambil',
                'data' => $instructors
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data instructor',
            ], 500);
        }
    }
}
