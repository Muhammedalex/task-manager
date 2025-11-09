<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTasksRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'sometimes',
                'string',
                Rule::in(['pending', 'in_progress', 'completed', 'canceled']),
            ],
            'assigned_to' => ['sometimes', 'integer', 'exists:users,id'],
            'due_date_from' => ['sometimes', 'date'],
            'due_date_to' => ['sometimes', 'date', 'after_or_equal:due_date_from'],
            'search' => ['sometimes', 'string', 'max:255'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
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
            'status.string' => 'The status must be a string.',
            'status.in' => 'The status must be one of: pending, in_progress, completed, canceled.',
            'assigned_to.integer' => 'The assigned to field must be an integer.',
            'assigned_to.exists' => 'The selected user does not exist.',
            'due_date_from.date' => 'The due date from must be a valid date.',
            'due_date_to.date' => 'The due date to must be a valid date.',
            'due_date_to.after_or_equal' => 'The due date to must be after or equal to due date from.',
            'search.string' => 'The search must be a string.',
            'search.max' => 'The search may not be greater than 255 characters.',
            'page.integer' => 'The page must be an integer.',
            'page.min' => 'The page must be at least 1.',
            'per_page.integer' => 'The per page must be an integer.',
            'per_page.min' => 'The per page must be at least 1.',
            'per_page.max' => 'The per page may not be greater than 100.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert query parameters to filters array format
        $this->merge([
            'page' => $this->query('page', 1),
            'per_page' => $this->query('per_page', 15),
        ]);
    }
}

