<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\Product\UpdateRequest;
use App\Http\Requests\Api\Product\ProductRequest;

use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use App\Models\Product;
use App\Classes\ApiResponse;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index()
    {
        $products = Product::with('category')
                          ->orderBy('updated_at', 'desc')
                          ->orderBy('created_at', 'desc')
                          ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    public function store(ProductRequest $request)
    {
        try {
            $product = $this->productService->createProduct($request->validated());
            return ApiResponse::sendResponse(new ProductResource($product), 'Product created successfully');
        } catch (\Exception $e) {
            return ApiResponse::rollback($e, 'Failed to create product'. $e);
        }
    }

    public function show($id)
    {
        try {
            $product = Product::with('sizes')->find($id);
            if (!$product) {
                return ApiResponse::NoSearch(null, 'Product not found');
            }
            return ApiResponse::sendResponse(new ProductResource($product), 'Product retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::rollback($e, 'Failed to retrieve product');
        }
    }

    public function update(UpdateRequest $request, $id)
    {
        try {
            $product = Product::find($id);
            if (!$product) {
                return ApiResponse::NoSearch(null, 'Product not found');
            }

            $data = $request->validated();
            
            // Xử lý upload ảnh nếu có file mới
            if ($request->hasFile('images')) {
                $uploadedImages = [];
                foreach ($request->file('images') as $image) {
                    $result = Cloudinary::upload($image->getRealPath());
                    $uploadedImages[] = $result->getSecurePath();
                }
                $data['images'] = $uploadedImages;
            } else if (isset($data['images']) && is_array($data['images'])) {
                // Giữ nguyên mảng URL ảnh cũ nếu không có file upload mới
            } else {
                unset($data['images']);
            }

            $updatedProduct = $this->productService->updateProduct($product, $data);
            return ApiResponse::sendResponse(
                new ProductResource($updatedProduct), 
                'Product updated successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::rollback($e, 'Failed to update product');
        }
    }

    public function destroy($id)
    {
        try {
            $product = Product::find($id);
            if (!$product) {
                return ApiResponse::NoSearch(null, 'Product not found');
            }
            $this->productService->deleteProduct($product);
            return ApiResponse::sendResponse(null, 'Product deleted successfully');
        } catch (\Exception $e) {
            return ApiResponse::rollback($e, 'Failed to delete product');
        }
    }

    public function getTrashed()
    {
        try {
            $trashedProducts = $this->productService->getTrashedProducts();
            return ApiResponse::sendResponse(ProductResource::collection($trashedProducts), 'Trashed products retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::rollback($e, 'Failed to retrieve trashed products');
        }
    }

    public function restore($id)
    {
        try {
            $restoredProduct = $this->productService->restoreProduct($id);
            if (!$restoredProduct) {
                return ApiResponse::NoSearch(null, 'Product not found or already restored');
            }
            return ApiResponse::sendResponse(new ProductResource($restoredProduct), 'Product restored successfully');
        } catch (\Exception $e) {
            return ApiResponse::rollback($e, 'Failed to restore product');
        }
    }

    public function upload(Request $request)
    {
        try {
            if (!$request->hasFile('images')) {
                return response()->json(['error' => 'No image uploaded'], 400);
            }

            $uploadedImages = [];
            foreach ($request->file('images') as $image) {
                // Sử dụng Cloudinary Laravel SDK
                $result = Cloudinary::upload($image->getRealPath());
                $uploadedImages[] = $result->getSecurePath();
            }

            return response()->json($uploadedImages);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
