<?php

namespace App\Http\Requests\Api\Category;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => ['required','string', 'max:255'],
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
