<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreIncidentReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'incident_type' => 'required|in:observasi,konflik,perambahan,device_alert,system',
            'location' => 'required|string|max:255',
            'description' => 'required|string|min:10|max:1000',
            'animal_code' => 'nullable|string|max:50',
            'photo_url' => [
                'nullable',
                'url',
                'regex:/\.(jpg|jpeg|png|gif|webp|bmp|svg)(\?.*)?$/i',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'incident_type.required' => 'Jenis insiden wajib diisi.',
            'incident_type.in' => 'Jenis insiden tidak valid.',
            'location.required' => 'Lokasi wajib diisi.',
            'location.max' => 'Lokasi maksimal 255 karakter.',
            'description.required' => 'Deskripsi wajib diisi.',
            'description.min' => 'Deskripsi minimal 10 karakter.',
            'description.max' => 'Deskripsi maksimal 1000 karakter.',
            'animal_code.max' => 'Kode satwa maksimal 50 karakter.',
            'photo_url.url' => 'Format URL foto tidak valid.',
            // ✅ Pesan kustom untuk validasi regex
            'photo_url.regex' => 'URL foto harus berakhiran dengan ekstensi gambar (.jpg, .png, .gif, .webp, .bmp, .svg).',
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