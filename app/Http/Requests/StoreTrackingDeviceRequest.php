<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreTrackingDeviceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'animal_id' => 'nullable|integer|exists:animals,animal_id|unique:tracking_devices,animal_id',
            'model' => 'required|string|max:100',
            'installation_date' => 'nullable|date|before_or_equal:today',
            'battery_life_months' => 'nullable|integer|min:1|max:60',
            'battery_level' => 'nullable|integer|min:0|max:100',
            'signal_strength' => 'nullable|integer|min:0|max:5',
            'device_condition' => 'required|in:normal,damaged,lost',
        ];
    }

    public function messages(): array
    {
        return [
            'animal_id.unique' => 'Satwa ini sudah memiliki perangkat aktif.',
            'device_condition.in' => 'Kondisi harus: normal, damaged, atau lost.',
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