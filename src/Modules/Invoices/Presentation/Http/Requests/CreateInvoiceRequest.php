<?php

declare(strict_types=1);

namespace Modules\Invoices\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<string>> */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'product_lines' => ['sometimes', 'array'],
            'product_lines.*.name' => ['required_with:product_lines.*', 'string', 'max:255'],
            'product_lines.*.quantity' => ['required_with:product_lines.*', 'integer', 'min:0'],
            'product_lines.*.unit_price' => ['required_with:product_lines.*', 'integer', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'customer_name.required' => 'Customer name is required.',
            'customer_email.required' => 'Customer email is required.',
            'customer_email.email' => 'Customer email must be a valid email address.',
            'product_lines.*.name.required_with' => 'Product name is required when product line is provided.',
            'product_lines.*.quantity.required_with' => 'Quantity is required when product line is provided.',
            'product_lines.*.quantity.integer' => 'Quantity must be an integer.',
            'product_lines.*.quantity.min' => 'Quantity cannot be negative.',
            'product_lines.*.unit_price.required_with' => 'Unit price is required when product line is provided.',
            'product_lines.*.unit_price.integer' => 'Unit price must be an integer.',
            'product_lines.*.unit_price.min' => 'Unit price cannot be negative.',
        ];
    }
}