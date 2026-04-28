<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\CourseCategory;

class CategoryController extends Controller
{
    private const CACHE_TTL = 60; // 60 seconds

    public function index()
    {
        try {
            // Try to get from cache
            $cacheKey = 'categories_all';
            $categories = Cache::remember($cacheKey, self::CACHE_TTL, function () {
                return CourseCategory::all();
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Data kategori berhasil diambil',
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data kategori',
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $cacheKey = "category_{$id}";
            $category = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($id) {
                return CourseCategory::with('courses')->find($id);
            });

            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Detail kategori',
                'data' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil detail kategori',
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100|unique:course_categories,name',
                'description' => 'nullable|string',
                'icon' => 'nullable|string'
            ]);

            $category = CourseCategory::create($validated);

            // Clear cache after creating
            Cache::forget('categories_all');

            return response()->json([
                'status' => 'success',
                'message' => 'Kategori berhasil dibuat',
                'data' => $category
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
                'message' => 'Gagal membuat kategori',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $category = CourseCategory::find($id);

            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:100|unique:course_categories,name,' . $id,
                'description' => 'nullable|string',
                'icon' => 'nullable|string'
            ]);

            $category->update($validated);

            // Clear cache after updating
            Cache::forget('categories_all');
            Cache::forget("category_{$id}");

            return response()->json([
                'status' => 'success',
                'message' => 'Kategori berhasil diupdate',
                'data' => $category
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
                'message' => 'Gagal mengupdate kategori',
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $category = CourseCategory::with('courses')->find($id);

            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            if ($category->courses()->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kategori tidak bisa dihapus karena masih digunakan'
                ], 400);
            }

            $category->delete();

            // Clear cache after deleting
            Cache::forget('categories_all');
            Cache::forget("category_{$id}");

            return response()->json([
                'status' => 'success',
                'message' => 'Kategori berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus kategori',
            ], 500);
        }
    }
}
