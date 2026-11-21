<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreSpeciesRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'common_name' => 'required|string|max:100',
            'scientific_name' => 'required|string|max:150|unique:species,scientific_name',
            'conservation_status' => 'required|in:Least Concern,Near Threatened,Vulnerable,Endangered,Critically Endangered,Extinct',
        ];
    }

    public function messages(): array
    {
        return [
            'common_name.required' => 'Nama umum wajib diisi.',
            'scientific_name.required' => 'Nama ilmiah wajib diisi.',
            'scientific_name.unique' => 'Nama ilmiah sudah terdaftar.',
            'conservation_status.in' => 'Status konservasi tidak valid.',
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