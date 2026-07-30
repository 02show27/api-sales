<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    /**
     * Permite procesar la solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 1. Asignamos reglas de validación para crear una venta.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'bail',
                'required',
                'integer',
                Rule::in([1]),
                'exists:users,id',
            ],

            'items' => [
                'bail',
                'required',
                'array',
                'min:1',
            ],

            'items.*' => [
                'required',
                'array',
            ],

            'items.*.product_id' => [
                'bail',
                'required',
                'integer',
                'distinct',
                'exists:products,id',
            ],

            'items.*.quantity' => [
                'bail',
                'required',
                'integer',
                'min:1',
            ],
        ];
    }

    /**
     *2. Asignamos mensajes personalizados para las reglas de validación.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'El usuario es obligatorio.',
            'user_id.integer' => 'El identificador del usuario debe ser un número entero.',
            'user_id.in' => 'Para esta prueba técnica solo se permite el usuario con ID 1.',
            'user_id.exists' => 'El usuario seleccionado no existe.',

            'items.required' => 'La venta debe contener al menos un producto.',
            'items.array' => 'Los productos deben enviarse en un arreglo.',
            'items.min' => 'La venta debe contener al menos un producto.',

            'items.*.array' => 'Cada elemento de la venta debe tener una estructura válida.',

            'items.*.product_id.required' => 'El producto es obligatorio.',
            'items.*.product_id.integer' => 'El identificador del producto debe ser un número entero.',
            'items.*.product_id.distinct' => 'No se puede repetir un producto en la misma venta.',
            'items.*.product_id.exists' => 'Uno de los productos seleccionados no existe.',

            'items.*.quantity.required' => 'La cantidad es obligatoria.',
            'items.*.quantity.integer' => 'La cantidad debe ser un número entero.',
            'items.*.quantity.min' => 'La cantidad debe ser mayor que cero.',
        ];
    }

    /**
     * 3. Asignamos nombres legibles para los campos.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'user_id' => 'usuario',
            'items' => 'productos',
            'items.*.product_id' => 'producto',
            'items.*.quantity' => 'cantidad',
        ];
    }
}
