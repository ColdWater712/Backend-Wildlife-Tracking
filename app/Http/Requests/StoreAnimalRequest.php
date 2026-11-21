<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAnimalRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'species_id' => 'required|integer|exists:species,species_id',
            'name' => 'required|string|max:100|unique:animals,name',
            'birth_date' => 'nullable|date|before_or_equal:today',
            'gender' => 'required|in:male,female,unknown',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'species_id.required' => 'Spesies wajib dipilih.',
            'species_id.integer' => 'The species id field must be an integer.',
            'species_id.exists' => 'Data spesies tidak ditemukan.',
            'name.required' => 'Nama satwa wajib diisi.',
            'name.unique' => 'Nama/kode satwa sudah digunakan.',
            'name.max' => 'Nama satwa maksimal 100 karakter.',
            'gender.in' => 'Jenis kelamin harus: male, female, atau unknown.',
            'birth_date.before_or_equal' => 'Tanggal lahir tidak boleh di masa depan.',
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