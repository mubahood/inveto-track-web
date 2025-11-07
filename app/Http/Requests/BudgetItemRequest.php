<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class BudgetItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policies
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $isUpdate = $this->has('id') && $this->input('id') != null;

        return [
            'id' => 'nullable|integer|exists:budget_items,id',
            'budget_program_id' => 'required|integer|exists:budget_programs,id',
            'budget_item_category_id' => 'required|integer|exists:budget_item_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'quantity' => 'required|numeric|min:0',
            'unit_cost' => 'required|numeric|min:0',
            'total_cost' => 'required|numeric|min:0',
            'financial_period_id' => 'nullable|integer|exists:financial_periods,id',
            'details' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'budget_program_id.required' => 'Budget program is required.',
            'budget_program_id.exists' => 'Selected budget program does not exist.',
            'budget_item_category_id.required' => 'Budget category is required.',
            'budget_item_category_id.exists' => 'Selected budget category does not exist.',
            'name.required' => 'Item name is required.',
            'name.max' => 'Item name must not exceed 255 characters.',
            'quantity.required' => 'Quantity is required.',
            'quantity.min' => 'Quantity must be greater than or equal to 0.',
            'unit_cost.required' => 'Unit cost is required.',
            'unit_cost.min' => 'Unit cost must be greater than or equal to 0.',
            'total_cost.required' => 'Total cost is required.',
            'total_cost.min' => 'Total cost must be greater than or equal to 0.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'code' => 0,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
                'data' => null,
            ], 422)
        );
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Calculate total_cost if not provided
        if ($this->has('quantity') && $this->has('unit_cost')) {
            $quantity = (float) $this->input('quantity', 0);
            $unitCost = (float) $this->input('unit_cost', 0);
            
            $this->merge([
                'total_cost' => $quantity * $unitCost,
            ]);
        }

        // Sanitize string inputs
        if ($this->has('name')) {
            $this->merge([
                'name' => strip_tags($this->input('name')),
            ]);
        }

        if ($this->has('description')) {
            $this->merge([
                'description' => strip_tags($this->input('description')),
            ]);
        }

        if ($this->has('details')) {
            $this->merge([
                'details' => strip_tags($this->input('details')),
            ]);
        }
    }
}
