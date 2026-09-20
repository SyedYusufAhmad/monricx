<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_email' => ['required', 'email:rfc', 'max:255'],
            'customer_name' => ['required', 'string', 'max:255'],
            'country_code' => ['required', Rule::in(['IN'])],
            'state' => ['required', Rule::in(config('monricx.indian_states'))],
            'address_line_1' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'postal_code' => ['required', 'string', 'regex:/^[1-9][0-9]{5}$/'],
            'customer_phone' => ['required', 'string', 'max:32', 'regex:/^[0-9+()\-\s]{8,32}$/'],
            'payment_option' => ['required', Rule::in(['razorpay', 'cash_on_delivery'])],
            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_email.required' => 'Enter a valid email',
            'customer_email.email' => 'Enter a valid email',
            'customer_name.required' => 'Enter a full name',
            'state.required' => 'Please choose shipping destination',
            'state.in' => 'Please choose shipping destination',
            'address_line_1.required' => 'Enter an address',
            'city.required' => 'Enter a city',
            'postal_code.required' => 'Enter a postal code',
            'postal_code.regex' => 'Enter a valid 6-digit postal code',
            'customer_phone.required' => 'Enter a phone number',
            'customer_phone.regex' => 'Enter a phone number',
            'terms.accepted' => "Please indicate that you agree to the store's policies",
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('payment_option')) {
            $this->merge(['payment_option' => 'razorpay']);
        }
    }
}
