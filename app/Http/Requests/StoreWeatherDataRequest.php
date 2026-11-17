<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreWeatherDataRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'reading_id' => 'nullable|integer|exists:gps_readings,reading_id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'observed_at' => 'required|date',
            'temperature_c' => 'nullable|numeric|between:-50,60',
            'rainfall_mm' => 'nullable|numeric|min:0',
            'humidity_percent' => 'nullable|numeric|between:0,100',
            'pressure_hpa' => 'nullable|numeric|between:800,1100',
            'source' => 'required|string|max:50',
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