<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class GenericModelRequest extends FormRequest
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
        $rules = [
            'id' => 'nullable|integer',
            'temp_file_field' => 'nullable|string',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf|max:10240',
        ];

        // Add common validation rules
        if ($this->has('name')) {
            $rules['name'] = 'required|string|max:255';
        }

        if ($this->has('description')) {
            $rules['description'] = 'nullable|string|max:1000';
        }

        if ($this->has('amount')) {
            $rules['amount'] = 'required|numeric|min:0';
        }

        if ($this->has('quantity')) {
            $rules['quantity'] = 'required|numeric|min:0';
        }

        if ($this->has('date')) {
            $rules['date'] = 'required|date';
        }

        if ($this->has('email')) {
            $rules['email'] = 'nullable|email|max:255';
        }

        if ($this->has('phone_number') || $this->has('phone')) {
            $rules['phone_number'] = 'nullable|string|max:20';
            $rules['phone'] = 'nullable|string|max:20';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'name.max' => 'Name must not exceed 255 characters.',
            'description.max' => 'Description must not exceed 1000 characters.',
            'amount.required' => 'Amount is required.',
            'amount.min' => 'Amount must be greater than or equal to 0.',
            'quantity.required' => 'Quantity is required.',
            'quantity.min' => 'Quantity must be greater than or equal to 0.',
            'date.required' => 'Date is required.',
            'date.date' => 'Invalid date format.',
            'email.email' => 'Please provide a valid email address.',
            'photo.file' => 'Invalid file uploaded.',
            'photo.mimes' => 'File must be: jpg, jpeg, png, gif, or pdf.',
            'photo.max' => 'File size must not exceed 10MB.',
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
        $merged = [];

        // Sanitize string inputs
        if ($this->has('name')) {
            $merged['name'] = strip_tags($this->input('name'));
        }

        if ($this->has('description')) {
            $merged['description'] = strip_tags($this->input('description'));
        }

        if ($this->has('details')) {
            $merged['details'] = strip_tags($this->input('details'));
        }

        if ($this->has('notes')) {
            $merged['notes'] = strip_tags($this->input('notes'));
        }

        // Normalize email
        if ($this->has('email')) {
            $merged['email'] = strtolower(trim($this->input('email')));
        }

        // Clean phone numbers
        if ($this->has('phone_number')) {
            $merged['phone_number'] = preg_replace('/[^0-9+]/', '', $this->input('phone_number'));
        }

        if ($this->has('phone')) {
            $merged['phone'] = preg_replace('/[^0-9+]/', '', $this->input('phone'));
        }

        // Ensure numeric fields are properly typed
        if ($this->has('amount')) {
            $merged['amount'] = (float) $this->input('amount');
        }

        if ($this->has('quantity')) {
            $merged['quantity'] = (float) $this->input('quantity');
        }

        if ($this->has('unit_cost')) {
            $merged['unit_cost'] = (float) $this->input('unit_cost');
        }

        if ($this->has('total_cost')) {
            $merged['total_cost'] = (float) $this->input('total_cost');
        }

        if (!empty($merged)) {
            $this->merge($merged);
        }
    }
}
