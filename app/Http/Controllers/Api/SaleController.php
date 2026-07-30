<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SaleController extends Controller
{
    /**
     * Registra una venta, sus detalles y actualiza el stock.
     *
     * @throws ValidationException
     */

    /**
     * Retorna las ventas con el detalle de productos vendidos.
     */
    public function index(): JsonResponse
    {
        $sales = Sale::query()
            ->select('id', 'total', 'created_at')
            ->with([
                'details:id,sale_id,product_id,quantity,subtotal',
                'details.product:id,name',
            ])
            ->latest('id')
            ->get()
            ->map(function (Sale $sale): array {
                return [
                    'id' => $sale->id,
                    'total' => (float) $sale->total,
                    'created_at' => $sale->created_at->toDateString(),

                    'details' => $sale->details
                        ->map(function ($detail): array {
                            return [
                                'product' => $detail->product->name,
                                'quantity' => (int) $detail->quantity,
                                'subtotal' => (float) $detail->subtotal,
                            ];
                        })
                        ->values(),
                ];
            });

        return response()->json($sales, 200);
    }

    public function store(StoreSaleRequest $request): JsonResponse
    {
        $data = $request->validated();

        $sale = DB::transaction(function () use ($data): Sale {
            $sale = Sale::create([
                'user_id' => $data['user_id'],
                'total' => 0,
            ]);

            $totalInCents = 0;

            foreach ($data['items'] as $index => $item) {
                /*
                 * 1. Lo bloqueamos para evitar que dos ventas simultáneas utilicen el mismo stock antes de que una de ellas finalice.
                 */
                $product = Product::query()
                    ->lockForUpdate()
                    ->find($item['product_id']);

                if ($product === null) {
                    throw ValidationException::withMessages([
                        "items.$index.product_id" => [
                            'El producto seleccionado ya no se encuentra disponible.',
                        ],
                    ]);
                }

                $quantity = (int) $item['quantity'];

                if ($product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        "items.$index.quantity" => [
                            "Stock insuficiente para {$product->name}. "
                            ."Disponible: {$product->stock}; solicitado: {$quantity}.",
                        ],
                    ]);
                }

                /*
                 * 1. Los cálculos lo realizamos en centavos para evitar errores de precisión con números decimales.
                 */
                $unitPriceInCents = (int) round(
                    ((float) $product->price) * 100
                );

                $subtotalInCents = $unitPriceInCents * $quantity;
                $totalInCents += $subtotalInCents;

                $sale->details()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $unitPriceInCents / 100,
                    'subtotal' => $subtotalInCents / 100,
                ]);

                $product->decrement('stock', $quantity);
            }

            $sale->update([
                'total' => $totalInCents / 100,
            ]);

            return $sale->fresh();
        });

        return response()->json([
            'message' => 'Venta creada correctamente',
            'sale_id' => $sale->id,
            'total' => (float) $sale->total,
        ], 201);
    }

    /**
     * 1. Asignamos una lógica para eliminar una venta y devuelve sus productos al stock.
     */
    public function destroy(int $id): JsonResponse
    {
        DB::transaction(function () use ($id): void {
            /*
             * a) Bloqueamos la venta para evitar que dos solicitudes intenten eliminarla simultáneamente.
             */
            $sale = Sale::query()
                ->lockForUpdate()
                ->find($id);

            if ($sale === null) {
                throw new HttpException(404, 'Venta no encontrada.');
            }

            /*
             * b) Los productos se procesan de manera ordenada para mantener un orden consistente de bloqueo.
             */
            $details = $sale->details()
                ->orderBy('product_id')
                ->get();

            foreach ($details as $detail) {
                $product = Product::query()
                    ->lockForUpdate()
                    ->find($detail->product_id);

                if ($product === null) {
                    throw new HttpException(
                        409,
                        "No se pudo restaurar el producto {$detail->product_id}."
                    );
                }

                $product->increment('stock', $detail->quantity);
            }

            /*
             * Nota: SoftDeletes actualiza deleted_at, donde Los detalles se conservan como respaldo de la operación.
             */
            $sale->delete();
        });

        return response()->json([
            'message' => 'Venta eliminada correctamente',
        ], 200);
    }
}
