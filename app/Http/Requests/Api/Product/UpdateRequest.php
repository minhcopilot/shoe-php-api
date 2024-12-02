<?php
namespace App\Http\Requests\Api\Product;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Support\Facades\Log;
class UpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'size' => 'array',
            'size.*' => 'exists:sizes,id',

        ];
    }
}
