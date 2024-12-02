<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Exception;

class CategoryService
{
    protected $model;

    public function __construct(Category $cate)
    {
        $this->model = $cate;
    }

    // Tạo mới một category
    public function create($params)
    {
        try {
            // Không cần sử dụng 'status' nữa
            return $this->model->create($params);
        } catch (Exception $exception) {
            Log::error('Error creating category: ' . $exception->getMessage());
            return false;
        }
    }

    // Cập nhật thông tin của category
    public function update($id, array $validatedData)
    {
        // Find the category
        $category = Category::find($id);

        if (!$category) {
            return null; // Return null if category not found
        }

        // Update category
        $category->update($validatedData);

        return $category;
    }

    //Lấy thông tin danh mục
    public function getAll()
    {
        try
        {
            return $this->model->all(); 
        }
        catch(Exception $ex)
        {
            Log::error('Error get category: ' . $ex->getMessage());
            return false;
        }
    }

    //Lấy thông tin danh mục theo id
    public function getById($id)
    {
        try
        {
            return $this->model = Category::find($id);
        }
        catch(Exception $ex)
        {
            Log::error('Error get category: ' . $ex->getMessage());
            return 404;
        }
    }

    //Soft delete
    public function delete($id)
    {
        $category = Category::find($id);

        if ($category) {
            return $category->delete(); // Soft delete
        }

        return null;
    }
    //get all soft delete
    public function getTrashed()
    {
        return Category::onlyTrashed()->get();
    }


    //Restore soft delete
    public function restore($id)
    {
        $category = Category::onlyTrashed()->find($id);

        if ($category) {
            $category->restore(); // Restore the soft-deleted category
            return $category;
        }

        return null;
    }

}
