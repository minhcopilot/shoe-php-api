<?php

namespace App\Http\Requests\Api\Category;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => ['required', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Hãy nhập tên danh mục.',
            'name.max' => 'Tên danh mục không được quá 255 ký tự.',
        ];
    }
}
