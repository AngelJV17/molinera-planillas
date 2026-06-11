<?php
namespace App\Http\Requests\Catalog;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCatalogRequest extends FormRequest
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
        $catalogId = $this->route('catalog')?->id;

        return [
            'type'        => ['required', 'string', 'max:80'],
            'code'        => [
                'required',
                'string',
                'max:80',
                Rule::unique('catalogs')
                    ->where(fn($query) => $query->where('type', $this->type))
                    ->ignore($catalogId),
            ],
            'name'        => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'status'      => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'El tipo de catálogo es obligatorio.',
            'code.required' => 'El código es obligatorio.',
            'code.unique'   => 'Ya existe un catálogo con este tipo y código.',
            'name.required' => 'El nombre es obligatorio.',
        ];
    }
}
