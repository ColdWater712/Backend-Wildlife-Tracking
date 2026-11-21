<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateGeoZoneRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'zone_name' => 'sometimes|string|max:150',
            'zone_type' => 'sometimes|in:permukiman,perkebunan,jalan_raya,koridor_ekologis',
            'center_lat' => 'sometimes|numeric|between:-90,90',
            'center_lon' => 'sometimes|numeric|between:-180,180',
            'radius_km' => 'sometimes|numeric|min:0.1|max:100',
            'polygon_geojson' => 'sometimes|array',
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