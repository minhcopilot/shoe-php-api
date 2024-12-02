<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ProductService
{
     public function getProducts($search = null)
    {
        $query = Product::with(['category', 'sizes']);

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        return $query->paginate(100);
    }

    public function createProduct($data)
    {
        // Xử lý upload nhiều ảnh
        $images = [];
        if (isset($data['images'])) {
            foreach ($data['images'] as $image) {
                $uploadedImage = Cloudinary::upload($image->getRealPath());
                $images[] = $uploadedImage->getSecurePath();
            }
        }

        // Tạo sản phẩm với mảng ảnh
        $product = Product::create([
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => $data['price'],
            'stock' => $data['stock'],
            'category_id' => $data['category_id'],
            'images' => $images
        ]);

        // Thêm sizes cho sản phẩm nếu có
        if (isset($data['sizes'])) {
            $product->sizes()->attach($data['sizes']);
        }

        return $product;
    }

    public function updateProduct($product, $data)
    {
        try {
            // Cập nhật thông tin cơ bản
            $product->name = $data['name'];
            $product->description = $data['description'];
            $product->price = $data['price'];
            $product->stock = $data['stock'];
            $product->category_id = $data['category_id'];

            // Cập nhật ảnh nếu có
            if (isset($data['images'])) {
                $product->images = $data['images'];
            }
            // Không cập nhật images nếu không có trong data

            $product->save();

            // Cập nhật sizes nếu có
            if (isset($data['size'])) {
                $product->sizes()->sync($data['size']);
            }

            return $product;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function deleteProduct($product)
    {
        // Xóa ảnh trên Cloudinary trước khi soft delete
        foreach ($product->images as $image) {
            $publicId = $this->getPublicIdFromUrl($image);
            if ($publicId) {
                Cloudinary::destroy($publicId);
            }
        }

        // Soft delete sản phẩm
        return $product->delete();
    }

    public function getTrashedProducts()
    {
        return Product::onlyTrashed()->get();
    }

    public function restoreProduct($id)
    {
        $product = Product::withTrashed()->find($id);
        if ($product) {
            $product->restore();
            return $product;
        }
        return null;
    }

    // Helper function để lấy public_id từ Cloudinary URL
    private function getPublicIdFromUrl($url)
    {
        // URL Cloudinary có dạng: https://res.cloudinary.com/your-cloud-name/image/upload/v1234567890/public-id
        $pattern = '/\/v\d+\/([^\/]+)\.\w+$/';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
