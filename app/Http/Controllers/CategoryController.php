<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\Category\CreateRequest;
use App\Http\Requests\Api\Category\UpdateRequest;
use App\Classes\ApiResponse;
use App\Http\Resources\CategoryResource;
use App\Services\CategoryService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }
    // Get all
    public function index()
    {
        $categories = $this->categoryService->getAll();
        // return CategoryResource::collection($categories);
        return ApiResponse::sendResponse(CategoryResource::collection($categories),'Cate get Successful',201);

    }

    // Add cate
    public function store(CreateRequest $createRequest)
    {
        $requests = $createRequest->validated();
        $result = $this->categoryService->create($requests);

        if ($result) {
            return ApiResponse::sendResponse(new CategoryResource($result),'Cate create successfull', 201);
        }

        return response()->json([
            'msg' => 'Thêm mới lỗi, vui lòng thử lại.'
        ], 400); 
    }

    // Get by id
    public function show($id)
    {
        try {
            $category = $this->categoryService->getById($id);

            if ($category) {
                return ApiResponse::sendResponse(new CategoryResource($category), 'Lấy thông tin thành công!');
            }

            return ApiResponse::NoSearch([], 'Không tìm thấy danh mục!', 404);
        } catch (\Exception $e) {
            ApiResponse::rollback($e, 'Lỗi khi lấy thông tin danh mục.');
        }
    }

    // Update Cate
    public function update(UpdateRequest $request, $id)
    {
        try {           
            $validatedData = $request->validated();
            $result = $this->categoryService->update($id, $validatedData);

            if ($result) {
                return ApiResponse::sendResponse(
                    new CategoryResource($result),
                    'Category updated successfully!',
                    200
                );
            }

            return ApiResponse::NoSearch(
                [],
                'Failed to update category. Please try again.',
                404
            );
        } catch (\Exception $e) {
            return ApiResponse::rollback($e, 'An error occurred during update.');
        }
    }

    // Soft delete a category
    public function destroy($id)
    {
        try {
            $result = $this->categoryService->delete($id);
            $categories = $this->categoryService->getAll();
            if ($result) {
                return ApiResponse::sendResponse(
                    [$categories],
                    'Category deleted successfully!',
                    200
                );
            }

            return ApiResponse::NoSearch(
                [],
                'Failed to delete category. Please try again.',
                404
            );
        } catch (\Exception $e) {
            return ApiResponse::rollback($e, 'An error occurred while deleting the category.');
        }
    }

    // Get all deleted categories
    public function getTrashed()
    {
        try {
            $trashedCategories = $this->categoryService->getTrashed();

            return ApiResponse::sendResponse(
                CategoryResource::collection($trashedCategories),
                'Deleted categories retrieved successfully!',
                200
            );
        } catch (\Exception $e) {
            return ApiResponse::rollback($e, 'An error occurred while retrieving deleted categories.');
        }
    }

    // Restore a soft-deleted category
    public function restore($id)
    {
        try {
            $result = $this->categoryService->restore($id);

            if ($result) {
                return ApiResponse::sendResponse(
                    new CategoryResource($result),
                    'Category restored successfully!',
                    200
                );
            }

            return ApiResponse::sendResponse(
                [],
                'Failed to restore category. Please try again.',
                400
            );
        } catch (\Exception $e) {
            return ApiResponse::rollback($e, 'An error occurred while restoring the category.');
        }
    }
}
