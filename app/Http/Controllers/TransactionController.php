<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    private const CACHE_TTL = 60; // 60 seconds

    /**
     * Get detailed transaction data with JOINs
     * GET /api/transactions/detail
     * 
     * Joins: Users, Courses, Categories, Enrollments
     */
    public function detail(Request $request)
    {
        try {
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 15);
            $cacheKey = "transactions_detail_page_{$page}_per_{$perPage}";
            
            $transactions = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($perPage) {
                return Enrollment::select(
                    'enrollments.id',
                    'enrollments.status',
                    'enrollments.created_at as enrollment_date',
                    'users.id as student_id',
                    'users.name as student_name',
                    'users.email as student_email',
                    'courses.id as course_id',
                    'courses.name as course_name',
                    'courses.price',
                    'courses.description',
                    'course_categories.id as category_id',
                    'course_categories.name as category_name',
                    'instructors.name as instructor_name',
                    'instructors.email as instructor_email'
                )
                    ->join('users', 'enrollments.user_id', '=', 'users.id')
                    ->join('courses', 'enrollments.course_id', '=', 'courses.id')
                    ->join('course_categories', 'courses.category_id', '=', 'course_categories.id')
                    ->join('users as instructors', 'courses.id_user', '=', 'instructors.id')
                    ->orderByDesc('enrollments.created_at')
                    ->paginate($perPage)
                    ->toArray();
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Data transaksi detail berhasil diambil',
                'data' => $transactions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data transaksi',
            ], 500);
        }
    }
}
