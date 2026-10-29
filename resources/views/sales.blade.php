<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Ventas - API Sales</title>
    <script src="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-6xl mx-auto space-y-6">
        
   
        <div class="bg-white p-6 rounded-lg shadow-md flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-800">Panel de Control - API Sales de Ventas</h1>
            <div class="space-x-2">
                <button onclick="loadProducts()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Ver Productos</button>
                <button onclick="loadSales()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Ver Ventas</button>
            </div>
        </div>

      
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-semibold mb-4 text-gray-700">Registrar Nueva Venta (User ID: 1)</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">ID del Producto</label>
                    <input type="number" id="prod-id" class="mt-1 block w-full border border-gray-300 rounded-md p-2" value="1">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Cantidad</label>
                    <input type="number" id="prod-qty" class="mt-1 block w-full border border-gray-300 rounded-md p-2" value="2">
                </div>
                <div class="flex items-end">
                    <button onclick="createSale()" class="w-full bg-indigo-600 text-white py-2 rounded hover:bg-indigo-700">Registrar Venta</button>
                </div>
            </div>
            <div id="sale-response" class="text-sm font-semibold"></div>
        </div>

    
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Lista de Productos -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4 text-gray-700">Catálogo de Productos</h2>
                <div id="products-list" class="space-y-3 max-h-96 overflow-y-auto"></div>
            </div>

            <!-- Lista de Ventas -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4 text-gray-700">Historial de Ventas</h2>
                <div id="sales-list" class="space-y-3 max-h-96 overflow-y-auto"></div>
            </div>
        </div>

    </div>

    <script>

        async function loadProducts() {
            const res = await fetch('/api/products');
            const products = await res.json();
            const container = document.getElementById('products-list');
            container.innerHTML = '';
            products.forEach(p => {
                container.innerHTML += `
                    <div class="border p-3 rounded bg-gray-50 flex justify-between items-center">
                        <div>
                            <span class="font-bold text-gray-800">ID: ${p.id} - ${p.name}</span>
                            <p class="text-sm text-green-600">Precio: Bs. ${p.price}</p>
                        </div>
                        <span class="text-sm bg-blue-100 text-blue-800 px-2 py-1 rounded">Stock: ${p.stock}</span>
                    </div>
                `;
            });
        }

    
        async function createSale() {
            const prodId = parseInt(document.getElementById('prod-id').value);
            const qty = parseInt(document.getElementById('prod-qty').value);
            const resDiv = document.getElementById('sale-response');

            const response = await fetch('/api/sales', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    user_id: 1,
                    items: [{ product_id: prodId, quantity: qty }]
                })
            });

            const data = await response.json();
            if (response.ok) {
                resDiv.className = "text-sm font-semibold text-green-600";
                resDiv.innerText = `¡Éxito! ${data.message} (ID Venta: ${data.sale_id}, Total: Bs. ${data.total})`;
                loadProducts();
                loadSales();
            } else {
                resDiv.className = "text-sm font-semibold text-red-600";
                resDiv.innerText = `Error: ${data.message || JSON.stringify(data.errors)}`;
            }
        }

  
        async function loadSales() {
            const res = await fetch('/api/sales');
            const sales = await res.json();
            const container = document.getElementById('sales-list');
            container.innerHTML = '';
            
            if(sales.length === 0) {
                container.innerHTML = '<p class="text-gray-500">No hay ventas registradas.</p>';
                return;
            }

            sales.forEach(s => {
                let detailsHtml = s.details.map(d => `<li class="text-xs text-gray-600">- ${d.product}: ${d.quantity} u. (Subtotal: Bs. ${d.subtotal})</li>`).join('');
                container.innerHTML += `
                    <div class="border p-3 rounded bg-gray-50 space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-gray-800">Venta ID: ${s.id}</span>
                            <button onclick="deleteSale(${s.id})" class="bg-red-500 text-white text-xs px-2 py-1 rounded hover:bg-red-600">Eliminar</button>
                        </div>
                        <p class="text-sm font-semibold text-indigo-600">Total: Bs. ${s.total}</p>
                        <p class="text-xs text-gray-500">Fecha: ${s.created_at}</p>
                        <ul class="pl-2 border-l-2 border-gray-300">${detailsHtml}</ul>
                    </div>
                `;
            });
        }

      
        async function deleteSale(id) {
            if(!confirm(`¿Estás seguro de eliminar la venta ID ${id}? Se restaurará el stock.`)) return;

            const response = await fetch(`/api/sales/${id}`, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json' }
            });

            if(response.ok) {
                alert('Venta eliminada y stock restaurado correctamente.');
                loadProducts();
                loadSales();
            } else {
                alert('Ocurrió un error al eliminar la venta.');
            }
        }

        loadProducts();
        loadSales();
    </script>
</body>
</html>