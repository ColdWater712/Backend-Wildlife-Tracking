<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreGpsReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => 'required|integer|exists:tracking_devices,device_id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'recorded_at' => 'required|date',
            'altitude_meters' => 'nullable|numeric|min:0|max:8848', // Max: tinggi Everest
            'accuracy_meters' => 'nullable|numeric|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'device_id.required' => 'Device ID wajib diisi.',
            'device_id.exists' => 'Perangkat tidak ditemukan.',
            'latitude.required' => 'Latitude wajib diisi.',
            'latitude.between' => 'Latitude harus antara -90 dan 90.',
            'longitude.required' => 'Longitude wajib diisi.',
            'longitude.between' => 'Longitude harus antara -180 dan 180.',
            'recorded_at.required' => 'Waktu pencatatan wajib diisi.',
            'recorded_at.date' => 'Format waktu tidak valid.',
            'altitude_meters.max' => 'Altitude tidak boleh lebih dari 8848 meter.',
            'accuracy_meters.max' => 'Akurasi tidak boleh lebih dari 100 meter.',
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