<?php

namespace App\Http\Controllers;

use App\Models\Category;
use GuzzleHttp\Psr7\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/category/create",
     *     summary="Create a new category",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"category_name"},
     *             @OA\Property(property="category_name", type="string", example="Science Fiction")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Category created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category created successfully"),
     *             @OA\Property(property="category", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="category_name", type="string", example="Science Fiction"),
     *                 @OA\Property(property="created_at", type="string", example="2025-07-31T12:00:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", example="2025-07-31T12:00:00.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="category_name", type="array", @OA\Items(type="string", example="The category_name field is required."))
     *         )
     *     )
     * )
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_name' => 'required|string|max:255|unique:categories,category_name',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $category = new Category();
        $category->category_name = $request->input('category_name');
        $category->save();

        return response()->json([
            "message" => "Category created successfully",
            "category" => $category
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/category/list",
     *     summary="Get all categories",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of categories",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="categories",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="category_name", type="string", example="Books"),
     *                     @OA\Property(property="created_at", type="string", example="2024-07-31T10:15:00Z"),
     *                     @OA\Property(property="updated_at", type="string", example="2024-07-31T10:20:00Z")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function list()
    {
        $categories = Category::all();
        return response()->json([
            "categories" => $categories
        ], 200);
    }

    /**
     * @OA\Put(
     *     path="/api/category/update/{id}",
     *     summary="Update a category",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the category to update",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"category_name"},
     *             @OA\Property(property="category_name", type="string", example="Updated Category")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category updated successfully"),
     *             @OA\Property(
     *                 property="category",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="category_name", type="string", example="Updated Category"),
     *                 @OA\Property(property="created_at", type="string", example="2024-07-31T10:15:00Z"),
     *                 @OA\Property(property="updated_at", type="string", example="2024-07-31T10:20:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="category_name",
     *                 type="array",
     *                 @OA\Items(type="string", example="The category name has already been taken.")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category not found")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'category_name' => 'required|string|max:255|unique:categories,category_name,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 400);
        }
        $category->category_name = $request->input('category_name');
        $category->save();

        return response()->json([
            "message" => "Category updated successfully",
            "category" => $category
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/category/delete/{id}",
     *     summary="Delete a category",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the category to delete",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category not found")
     *         )
     *     )
     * )
     */
    public function delete($id)
    {
        $category = Category::findOrFail($id);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 400);
        }
        $category->delete();

        return response()->json([
            "message" => "Category deleted successfully"
        ], 200);
    }
}
