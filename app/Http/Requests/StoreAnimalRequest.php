<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAnimalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'species_id' => 'required|exists:species,species_id',
            'name' => 'required|string|max:100',
            'gender' => 'required|in:male,female,unknown',
            'birth_date' => 'nullable|date|before_or_equal:today',
        ];
    }

    public function messages(): array
    {
        return [
            'species_id.required' => 'Spesies wajib dipilih.',
            'species_id.exists' => 'Data spesies tidak ditemukan.',
            'name.required' => 'Nama satwa wajib diisi.',
            'name.max' => 'Nama satwa maksimal 100 karakter.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'gender.in' => 'Jenis kelamin harus: male, female, atau unknown.',
            'birth_date.date' => 'Format tanggal lahir tidak valid (YYYY-MM-DD).',
            'birth_date.before_or_equal' => 'Tanggal lahir tidak boleh di masa depan.',
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