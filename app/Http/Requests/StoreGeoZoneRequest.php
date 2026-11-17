<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreGeoZoneRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'zone_name' => 'required|string|max:150',
            'zone_type' => 'required|in:permukiman,perkebunan,jalan_raya,koridor_ekologis',
            'center_lat' => 'nullable|numeric|between:-90,90',
            'center_lon' => 'nullable|numeric|between:-180,180',
            'radius_km' => 'nullable|numeric|min:0.1|max:100',
            'polygon_geojson' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'zone_type.in' => 'Jenis zona harus: permukiman, perkebunan, jalan_raya, atau koridor_ekologis.',
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