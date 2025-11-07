<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required_with:password',
            'phone_number' => 'nullable|string|max:20',
            'company_name' => 'required|string|max:255',
            'currency' => 'required|string|in:UGX,USD,EUR,GBP,KES,TZS,RWF',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'first_name.max' => 'First name must not exceed 255 characters.',
            'last_name.required' => 'Last name is required.',
            'last_name.max' => 'Last name must not exceed 255 characters.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'company_name.required' => 'Company name is required.',
            'company_name.max' => 'Company name must not exceed 255 characters.',
            'currency.required' => 'Currency is required.',
            'currency.in' => 'Invalid currency selected. Choose from: UGX, USD, EUR, GBP, KES, TZS, RWF.',
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
        // Sanitize and normalize inputs
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim($this->input('email'))),
            ]);
        }

        if ($this->has('first_name')) {
            $this->merge([
                'first_name' => ucwords(strtolower(trim(strip_tags($this->input('first_name'))))),
            ]);
        }

        if ($this->has('last_name')) {
            $this->merge([
                'last_name' => ucwords(strtolower(trim(strip_tags($this->input('last_name'))))),
            ]);
        }

        if ($this->has('company_name')) {
            $this->merge([
                'company_name' => trim(strip_tags($this->input('company_name'))),
            ]);
        }

        if ($this->has('phone_number')) {
            // Remove non-numeric characters except +
            $phone = preg_replace('/[^0-9+]/', '', $this->input('phone_number'));
            $this->merge([
                'phone_number' => $phone,
            ]);
        }

        if ($this->has('currency')) {
            $this->merge([
                'currency' => strtoupper(trim($this->input('currency'))),
            ]);
        }
    }
}
