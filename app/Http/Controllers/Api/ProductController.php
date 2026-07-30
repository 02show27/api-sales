<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * 1. Funcion para obtener la lista de productos.
     * 2. Retorna un JSON con los productos.
     * 3. Cada producto incluye id, nombre, precio y stock.
     * 4. Ordena los productos por id de manera ascendente.
     * 5. Retorna un codigo de estado HTTP 200 ok.
     */
    public function index(): JsonResponse
    {
        $products = Product::query()
            ->select('id', 'name', 'price', 'stock')
            ->orderBy('id')
            ->get();

        return response()->json($products, 200);
    }
}
