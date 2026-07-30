<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class SaleApiTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_products_can_be_listed(): void
    {
        $response = $this->getJson('/api/products');

        $response
            ->assertOk()
            ->assertJsonCount(10)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'price',
                    'stock',
                ],
            ]);

        $firstProduct = $response->json('0');

        $this->assertArrayNotHasKey('image', $firstProduct);
    }

    public function test_sale_can_be_created(): void
    {
        $product1 = Product::findOrFail(1);
        $product2 = Product::findOrFail(2);

        $initialStock1 = $product1->stock;
        $initialStock2 = $product2->stock;

        $expectedSubtotal1 = round((float) $product1->price * 2, 2);
        $expectedSubtotal2 = round((float) $product2->price, 2);
        $expectedTotal = $expectedSubtotal1 + $expectedSubtotal2;

        $response = $this->postJson('/api/sales', [
            'user_id' => 1,
            'items' => [
                [
                    'product_id' => $product1->id,
                    'quantity' => 2,
                ],
                [
                    'product_id' => $product2->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJson([
                'message' => 'Venta creada correctamente',
                'total' => $expectedTotal,
            ])
            ->assertJsonStructure([
                'message',
                'sale_id',
                'total',
            ]);

        $saleId = $response->json('sale_id');

        $this->assertDatabaseHas('sales', [
            'id' => $saleId,
            'user_id' => 1,
            'total' => $expectedTotal,
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('sale_details', [
            'sale_id' => $saleId,
            'product_id' => $product1->id,
            'quantity' => 2,
            'price' => $product1->price,
            'subtotal' => $expectedSubtotal1,
        ]);

        $this->assertDatabaseHas('sale_details', [
            'sale_id' => $saleId,
            'product_id' => $product2->id,
            'quantity' => 1,
            'price' => $product2->price,
            'subtotal' => $expectedSubtotal2,
        ]);

        $this->assertSame(
            $initialStock1 - 2,
            $product1->fresh()->stock
        );

        $this->assertSame(
            $initialStock2 - 1,
            $product2->fresh()->stock
        );
    }

    public function test_sale_requires_at_least_one_item(): void
    {
        $response = $this->postJson('/api/sales', [
            'user_id' => 1,
            'items' => [],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_details', 0);
    }

    public function test_quantity_must_be_greater_than_zero(): void
    {
        $response = $this->postJson('/api/sales', [
            'user_id' => 1,
            'items' => [
                [
                    'product_id' => 1,
                    'quantity' => 0,
                ],
            ],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'items.0.quantity',
            ]);

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_details', 0);
    }

    public function test_product_must_exist(): void
    {
        $response = $this->postJson('/api/sales', [
            'user_id' => 1,
            'items' => [
                [
                    'product_id' => 999999,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'items.0.product_id',
            ]);

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_only_static_user_one_is_allowed(): void
    {
        $response = $this->postJson('/api/sales', [
            'user_id' => 2,
            'items' => [
                [
                    'product_id' => 1,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id']);

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_duplicate_products_are_rejected(): void
    {
        $response = $this->postJson('/api/sales', [
            'user_id' => 1,
            'items' => [
                [
                    'product_id' => 1,
                    'quantity' => 1,
                ],
                [
                    'product_id' => 1,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'items.1.product_id',
            ]);

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_details', 0);
    }

    public function test_sale_is_not_created_when_stock_is_insufficient(): void
    {
        $product = Product::findOrFail(1);
        $initialStock = $product->stock;

        $response = $this->postJson('/api/sales', [
            'user_id' => 1,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => $initialStock + 1,
                ],
            ],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'items.0.quantity',
            ]);

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_details', 0);

        $this->assertSame(
            $initialStock,
            $product->fresh()->stock
        );
    }

    public function test_transaction_rolls_back_all_changes_when_an_item_has_insufficient_stock(): void
    {
        $product1 = Product::findOrFail(1);
        $product2 = Product::findOrFail(2);

        $initialStock1 = $product1->stock;
        $initialStock2 = $product2->stock;

        $response = $this->postJson('/api/sales', [
            'user_id' => 1,
            'items' => [
                [
                    'product_id' => $product1->id,
                    'quantity' => 1,
                ],
                [
                    'product_id' => $product2->id,
                    'quantity' => $initialStock2 + 1,
                ],
            ],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'items.1.quantity',
            ]);

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_details', 0);

        $this->assertSame(
            $initialStock1,
            $product1->fresh()->stock
        );

        $this->assertSame(
            $initialStock2,
            $product2->fresh()->stock
        );
    }

    public function test_sales_can_be_listed_with_product_details(): void
    {
        $product = Product::findOrFail(1);
        $expectedSubtotal = round((float) $product->price * 2, 2);

        $creationResponse = $this->postJson('/api/sales', [
            'user_id' => 1,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $creationResponse->assertCreated();

        $saleId = $creationResponse->json('sale_id');

        $response = $this->getJson('/api/sales');

        $response
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $saleId)
            ->assertJsonPath('0.details.0.product', $product->name)
            ->assertJsonPath('0.details.0.quantity', 2)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'total',
                    'created_at',
                    'details' => [
                        '*' => [
                            'product',
                            'quantity',
                            'subtotal',
                        ],
                    ],
                ],
            ]);
        $this->assertSame(
            (float) $expectedSubtotal,
            (float) $response->json('0.total')
        );

        $this->assertSame(
            (float) $expectedSubtotal,
            (float) $response->json('0.details.0.subtotal')
        );
    }

    public function test_sale_can_be_deleted_and_stock_is_restored(): void
    {
        $product = Product::findOrFail(1);
        $initialStock = $product->stock;

        $creationResponse = $this->postJson('/api/sales', [
            'user_id' => 1,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $creationResponse->assertCreated();

        $saleId = $creationResponse->json('sale_id');

        $this->assertSame(
            $initialStock - 2,
            $product->fresh()->stock
        );

        $response = $this->deleteJson("/api/sales/{$saleId}");

        $response
            ->assertOk()
            ->assertExactJson([
                'message' => 'Venta eliminada correctamente',
            ]);

        $this->assertSoftDeleted('sales', [
            'id' => $saleId,
        ]);

        $this->assertDatabaseHas('sale_details', [
            'sale_id' => $saleId,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->assertSame(
            $initialStock,
            $product->fresh()->stock
        );

        $this->getJson('/api/sales')
            ->assertOk()
            ->assertJsonMissing([
                'id' => $saleId,
            ]);
    }

    public function test_deleted_sale_cannot_be_deleted_twice(): void
    {
        $product = Product::findOrFail(1);
        $initialStock = $product->stock;

        $creationResponse = $this->postJson('/api/sales', [
            'user_id' => 1,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $saleId = $creationResponse->json('sale_id');

        $this->deleteJson("/api/sales/{$saleId}")
            ->assertOk();

        $this->deleteJson("/api/sales/{$saleId}")
            ->assertNotFound()
            ->assertJson([
                'message' => 'Venta no encontrada.',
            ]);

        $this->assertSame(
            $initialStock,
            $product->fresh()->stock
        );
    }

    public function test_deleting_nonexistent_sale_returns_not_found(): void
    {
        $this->deleteJson('/api/sales/999999')
            ->assertNotFound()
            ->assertJson([
                'message' => 'Venta no encontrada.',
            ]);
    }
}
