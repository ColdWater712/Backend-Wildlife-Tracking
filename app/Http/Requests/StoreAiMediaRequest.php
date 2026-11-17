<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAiMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:jpg,jpeg,png,webp|max:5120',
            'report_id' => 'nullable|integer|exists:incident_reports,report_id',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'File gambar wajib diunggah.',
            'file.file' => 'Input harus berupa file.',
            'file.mimes' => 'File harus berupa gambar (JPG, JPEG, PNG, WEBP).',
            'file.max' => 'Ukuran file maksimal 5MB.',
            'report_id.exists' => 'Laporan insiden terkait tidak ditemukan.',
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