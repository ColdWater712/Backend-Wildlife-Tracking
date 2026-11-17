<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateSpeciesRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'common_name' => 'sometimes|string|max:100',
            'scientific_name' => ['sometimes', 'string', 'max:150', Rule::unique('species', 'scientific_name')->ignore($id, 'species_id')],
            'conservation_status' => 'sometimes|in:Least Concern,Near Threatened,Vulnerable,Endangered,Critically Endangered,Extinct',
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