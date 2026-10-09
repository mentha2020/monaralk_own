<?php

namespace App\Http\Requests;

use App\Modules\Shared\Rules\ContactNumber;
use Illuminate\Foundation\Http\FormRequest;

class StoreEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40', new ContactNumber],
            'message' => ['required', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'name',
            'email' => 'email',
            'phone' => 'phone number',
            'message' => 'message',
        ];
    }
}
