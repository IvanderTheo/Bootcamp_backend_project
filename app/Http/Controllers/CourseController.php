<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Course;

class CourseController extends Controller
{
    private const CACHE_TTL = 60; // 60 seconds

    public function index(Request $request)
    {
        try {
            // Create cache key based on query parameters
            $cacheKey = 'courses_' . md5(json_encode($request->query()));
            
            $courses = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($request) {
                $query = Course::with(['instructor', 'category']);

                // search
                if ($request->search) {
                    $query->where('name', 'like', '%' . $request->search . '%');
                }

                // sorting
                if ($request->sort_by) {
                    $query->orderBy($request->sort_by, $request->order ?? 'asc');
                }

                return $query->get()->map(function ($course) {
                    $rating = $course->rating ?? 0;

                    if ($rating >= 8.5) {
                        $rating_class = 'Top Rated';
                    } elseif ($rating >= 7) {
                        $rating_class = 'Recommended';
                    } else {
                        $rating_class = 'Regular';
                    }

                    return [
                        'id' => $course->id,
                        'name' => $course->name,
                        'description' => $course->description,
                        'price' => $course->price,
                        'rating' => $rating,
                        'rating_class' => $rating_class,
                        'category' => $course->category,
                        'instructor' => $course->instructor
                    ];
                });
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Data kursus berhasil diambil',
                'data' => $courses
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data kursus',
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $cacheKey = "course_{$id}";
            $course = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($id) {
                return Course::with('modules.lessons', 'instructor')->find($id);
            });

            if (!$course) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Detail kursus',
                'data' => $course
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil detail kursus',
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0',
                'category_id' => 'required|exists:course_categories,id',
                'quota' => 'nullable|integer|min:0',
            ]);

            if (auth()->user()->role !== 'instructor') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized - Only instructors can create courses'
                ], 403);
            }

            $course = Course::create([
                'id_user' => auth()->id(),
                ...$validated
            ]);

            // Clear cache after creating
            Cache::flush(); // or specific pattern

            return response()->json([
                'status' => 'success',
                'message' => 'Course berhasil dibuat',
                'data' => $course
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat course',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $course = Course::find($id);

            if (!$course) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            if ($course->id_user !== auth()->id()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized - You can only update your own courses'
                ], 403);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'price' => 'sometimes|numeric|min:0',
                'category_id' => 'sometimes|exists:course_categories,id',
                'quota' => 'sometimes|integer|min:0',
            ]);

            $course->update($validated);

            // Clear cache after updating
            Cache::forget("course_{$id}");
            Cache::flush();

            return response()->json([
                'status' => 'success',
                'message' => 'Course berhasil diupdate',
                'data' => $course
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengupdate course',
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $course = Course::find($id);

            if (!$course) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            if ($course->id_user !== auth()->id()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized - You can only delete your own courses'
                ], 403);
            }

            $course->delete();

            // Clear cache after deleting
            Cache::forget("course_{$id}");
            Cache::flush();

            return response()->json([
                'status' => 'success',
                'message' => 'Course berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus course',
            ], 500);
        }
    }
}
