<?php

namespace onamfc\EloquentJsonSchema\Examples\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|unique:users,email',
            'name' => 'required|string|min:2|max:255',
            'plan' => 'required|in:basic,pro,enterprise',
            'password' => 'required|string|min:8|confirmed',
        ];
    }
}