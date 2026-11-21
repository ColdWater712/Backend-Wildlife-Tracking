<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'username'  => 'nullable|string|max:100',
            'full_name' => 'nullable|string|max:100',
            'name'      => 'nullable|string|max:100',
            'email'     => 'required|email|max:150|unique:users,email',
            'password'  => 'required|string|min:6',
            'role'      => 'required|in:konservasionis_lapangan,peneliti_ekologi,administrator_sistem,pengambil_kebijakan',
            'role_id'   => 'nullable|integer|exists:roles,role_id',
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($v) {
            if (!$this->input('name') && !$this->input('full_name') && !$this->input('username')) {
                $v->errors()->add('name', 'Nama wajib diisi.');
            }
        });
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false, 'message' => $validator->errors()->first(),
            'data' => null, 'errors' => $validator->errors(),
        ], 422));
    }
}