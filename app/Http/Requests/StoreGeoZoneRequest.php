<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreGeoZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'zone_name' => 'required|string|max:150|regex:/^[a-zA-Z0-9\s\-]+$/',
            'zone_type' => 'required|in:permukiman,perkebunan,jalan_raya,koridor_ekologis',
            'center_lat' => 'required|numeric|between:-90,90',
            'center_lon' => 'required|numeric|between:-180,180',
            'radius_km' => 'required|numeric|min:0.1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'zone_name.required' => 'Nama zona wajib diisi.',
            'zone_name.max' => 'Nama zona maksimal 150 karakter.',
            'zone_name.regex' => 'Nama zona hanya boleh berisi huruf, angka, spasi, dan strip.',
            'zone_type.required' => 'Tipe zona wajib dipilih.',
            'zone_type.in' => 'Tipe zona tidak valid.',
            'center_lat.required' => 'Latitude pusat wajib diisi.',
            'center_lat.between' => 'Latitude harus antara -90 dan 90.',
            'center_lon.required' => 'Longitude pusat wajib diisi.',
            'center_lon.between' => 'Longitude harus antara -180 dan 180.',
            'radius_km.required' => 'Radius wajib diisi.',
            'radius_km.min' => 'Radius minimal 0.1 km.',
            'radius_km.max' => 'Radius maksimal 100 km.',
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