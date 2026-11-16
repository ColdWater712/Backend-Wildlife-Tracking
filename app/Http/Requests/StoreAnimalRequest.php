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
            'species_id' => 'required|integer|exists:species,species_id',
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9\s\-]+$/',
            ],
            'gender' => 'required|in:male,female,unknown',
            'birth_date' => 'nullable|date|before_or_equal:today',
        ];
    }

    public function messages(): array
    {
        return [
            'species_id.required' => 'Spesies wajib dipilih.',
            'species_id.integer' => 'The species id field must be an integer.', // ✅ FIX: Sesuai expected QA
            'species_id.exists' => 'Data spesies tidak ditemukan.',
            'name.required' => 'Nama satwa wajib diisi.',
            'name.max' => 'Nama satwa maksimal 100 karakter.',
            'name.regex' => 'Nama hanya boleh berisi huruf, angka, spasi, dan strip.',
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