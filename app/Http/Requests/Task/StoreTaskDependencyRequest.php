<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskDependencyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole('Manager');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'dependency_ids' => ['required', 'array', 'min:1'],
            'dependency_ids.*' => ['required', 'string', 'exists:tasks,code'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dependency_ids.required' => 'The dependency ids field is required.',
            'dependency_ids.array' => 'The dependency ids must be an array.',
            'dependency_ids.min' => 'The dependency ids must have at least one item.',
            'dependency_ids.*.required' => 'Each dependency code is required.',
            'dependency_ids.*.string' => 'Each dependency code must be a string.',
            'dependency_ids.*.exists' => 'One or more selected tasks do not exist.',
        ];
    }
}

