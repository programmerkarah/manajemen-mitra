<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRolesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $roleIds = Role::pluck('id')->toArray();

        return [
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'integer', Rule::in($roleIds)],
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
            'roles.required' => 'User harus memiliki minimal satu role.',
            'roles.array' => 'Format roles tidak valid.',
            'roles.min' => 'User harus memiliki minimal satu role.',
            'roles.*.required' => 'Role ID tidak boleh kosong.',
            'roles.*.integer' => 'Role ID harus berupa angka.',
            'roles.*.in' => 'Role yang dipilih tidak valid.',
        ];
    }
}
