<?php

namespace App\Http\Requests\Bank;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBankRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [

            'name' => [

                'required',
                'string',
                'max:100',

                Rule::unique('banks')
                    ->ignore(
                        $this->route('bank')
                    ),
            ],

            'code' => [
                'nullable',
                'string',
                'max:20',
            ],
        ];
    }
}
