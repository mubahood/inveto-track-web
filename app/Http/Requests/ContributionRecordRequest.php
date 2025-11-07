<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ContributionRecordRequest extends FormRequest
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
        return [
            'id' => 'nullable|integer|exists:contribution_records,id',
            'budget_program_id' => 'required|integer|exists:budget_programs,id',
            'treasurer_id' => 'required|integer|exists:users,id',
            'contributor_name' => 'required|string|max:255',
            'contributor_phone' => 'nullable|string|max:20',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|in:cash,mobile_money,bank_transfer,check',
            'reference_number' => 'nullable|string|max:100',
            'date' => 'required|date',
            'receipt_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
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
            'treasurer_id.required' => 'Treasurer is required.',
            'treasurer_id.exists' => 'Selected treasurer does not exist.',
            'contributor_name.required' => 'Contributor name is required.',
            'contributor_name.max' => 'Contributor name must not exceed 255 characters.',
            'contributor_phone.max' => 'Phone number must not exceed 20 characters.',
            'amount.required' => 'Contribution amount is required.',
            'amount.min' => 'Contribution amount must be greater than 0.',
            'payment_method.in' => 'Invalid payment method selected.',
            'date.required' => 'Contribution date is required.',
            'date.date' => 'Invalid date format.',
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
        // Sanitize string inputs
        if ($this->has('contributor_name')) {
            $this->merge([
                'contributor_name' => strip_tags($this->input('contributor_name')),
            ]);
        }

        if ($this->has('contributor_phone')) {
            // Remove non-numeric characters from phone
            $phone = preg_replace('/[^0-9+]/', '', $this->input('contributor_phone'));
            $this->merge([
                'contributor_phone' => $phone,
            ]);
        }

        if ($this->has('reference_number')) {
            $this->merge([
                'reference_number' => strip_tags($this->input('reference_number')),
            ]);
        }

        if ($this->has('receipt_number')) {
            $this->merge([
                'receipt_number' => strip_tags($this->input('receipt_number')),
            ]);
        }

        if ($this->has('notes')) {
            $this->merge([
                'notes' => strip_tags($this->input('notes')),
            ]);
        }

        // Ensure amount is numeric
        if ($this->has('amount')) {
            $this->merge([
                'amount' => (float) $this->input('amount'),
            ]);
        }
    }
}
