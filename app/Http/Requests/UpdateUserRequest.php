<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'name' => 'sometimes|string|max:100',
            'email' => ['sometimes', 'email', 'max:150', Rule::unique('users', 'email')->ignore($id, 'user_id')],
            'password' => 'sometimes|string|min:6',
            'role' => 'sometimes|in:konservasionis_lapangan,peneliti_ekologi,administrator_sistem,pengambil_kebijakan',
            'role_id' => 'sometimes|integer|exists:roles,role_id',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'data' => null,
            'errors' => $validator->errors(),
        ], 422));
    }
}