<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreSpeciesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'common_name' => 'required|string|max:100|regex:/^[a-zA-Z\s]+$/',
            'scientific_name' => 'required|string|max:150|unique:species,scientific_name|regex:/^[a-zA-Z\s]+$/',
            'conservation_status' => 'required|in:Least Concern,Near Threatened,Vulnerable,Endangered,Critically Endangered,Extinct',
        ];
    }

    public function messages(): array
    {
        return [
            'common_name.required' => 'Nama umum wajib diisi.',
            'common_name.max' => 'Nama umum maksimal 100 karakter.',
            'common_name.regex' => 'Nama umum hanya boleh berisi huruf dan spasi.',
            'scientific_name.required' => 'Nama ilmiah wajib diisi.',
            'scientific_name.unique' => 'Nama ilmiah sudah terdaftar.',
            'scientific_name.regex' => 'Nama ilmiah hanya boleh berisi huruf dan spasi.',
            'conservation_status.required' => 'Status konservasi wajib dipilih.',
            'conservation_status.in' => 'Status konservasi tidak valid.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }
}