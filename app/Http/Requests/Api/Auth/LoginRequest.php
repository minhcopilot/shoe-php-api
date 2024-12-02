<?php

namespace App\Http\Requests\Api\Auth;

use App\Http\Requests\BaseRequest;

class LoginRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'email' => 'required|string|email|exists:users,email',
            'password' => 'required|min:8',
        ];
    }

    public function messages()
    {
        return [
            'email.exists' => 'Email or password is incorrect',
        ];
    }
}