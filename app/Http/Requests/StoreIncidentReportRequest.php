<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncidentReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Izinkan semua user yang terautentikasi
    }

    public function rules(): array
    {
        return [
            'incident_type' => 'required|string|max:50',
            'animal_code'   => 'nullable|string|max:50',
            'location'      => 'required|string|max:255',
            'description'   => 'required|string',
            'photo_url'     => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'incident_type.required' => 'Tipe insiden wajib diisi.',
            'location.required'      => 'Lokasi wajib diisi.',
            'description.required'   => 'Deskripsi wajib diisi.',
        ];
    }
}