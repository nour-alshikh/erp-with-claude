<?php

namespace App\Modules\Inventory\Services;

use App\Base\BaseService;
use App\Jobs\Inventory\CheckLowStockJob;
use App\Modules\Inventory\Models\StockLayer;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Repositories\Interfaces\ProductRepositoryInterface;
use App\Modules\Inventory\Repositories\Interfaces\StockMovementRepositoryInterface;
use App\Modules\Inventory\Repositories\Interfaces\WarehouseRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService extends BaseService
{
    public function __construct(
        private readonly ProductRepositoryInterface      $products,
        private readonly WarehouseRepositoryInterface    $warehouses,
        private readonly StockMovementRepositoryInterface $movements,
    ) {}

    // ── Products ──────────────────────────────────────────────────────────────

    public function listProducts(): LengthAwarePaginator
    {
        return $this->products->paginate();
    }

    public function getProduct(int $id)
    {
        return $this->products->findOrFail($id);
    }

    public function createProduct(array $data)
    {
        return $this->products->create($data);
    }

    public function updateProduct(int $id, array $data)
    {
        return $this->products->update($this->products->findOrFail($id), $data);
    }

    public function deleteProduct(int $id): void
    {
        $this->products->delete($this->products->findOrFail($id));
    }

    // ── Warehouses ────────────────────────────────────────────────────────────

    public function listWarehouses()
    {
        return $this->warehouses->all();
    }

    public function getWarehouse(int $id)
    {
        return $this->warehouses->findOrFail($id);
    }

    public function createWarehouse(array $data)
    {
        return $this->warehouses->create($data);
    }

    public function updateWarehouse(int $id, array $data)
    {
        return $this->warehouses->update($this->warehouses->findOrFail($id), $data);
    }

    public function deleteWarehouse(int $id): void
    {
        $this->warehouses->delete($this->warehouses->findOrFail($id));
    }

    // ── Stock Movements ───────────────────────────────────────────────────────

    public function listMovements(): LengthAwarePaginator
    {
        return $this->movements->paginate();
    }

    public function stockIn(array $data, int $companyId): StockMovement
    {
        return DB::transaction(function () use ($data, $companyId) {
            $movement = $this->movements->create([
                'company_id'    => $companyId,
                'product_id'    => $data['product_id'],
                'warehouse_id'  => $data['warehouse_id'],
                'type'          => 'in',
                'qty'           => $data['qty'],
                'cost_per_unit' => $data['cost_per_unit'],
                'date'          => $data['date'] ?? now()->toDateString(),
            ]);

            StockLayer::create([
                'company_id'    => $companyId,
                'product_id'    => $data['product_id'],
                'warehouse_id'  => $data['warehouse_id'],
                'qty_remaining' => $data['qty'],
                'cost_per_unit' => $data['cost_per_unit'],
                'date'          => $data['date'] ?? now()->toDateString(),
            ]);

            return $movement->load(['product', 'warehouse']);
        });
    }

    public function stockOut(array $data, int $companyId): StockMovement
    {
        return DB::transaction(function () use ($data, $companyId) {
            $product   = $this->products->findOrFail($data['product_id']);
            $available = $product->currentStock($data['warehouse_id']);

            if ($available < $data['qty']) {
                throw ValidationException::withMessages([
                    'qty' => "Insufficient stock. Available: {$available}, Requested: {$data['qty']}.",
                ]);
            }

            $cogsTotal   = $this->reduceLayers($data['product_id'], $data['warehouse_id'], $data['qty']);
            $costPerUnit = $data['qty'] > 0 ? intdiv($cogsTotal, $data['qty']) : 0;

            $movement = $this->movements->create([
                'company_id'    => $companyId,
                'product_id'    => $data['product_id'],
                'warehouse_id'  => $data['warehouse_id'],
                'type'          => 'out',
                'qty'           => $data['qty'],
                'cost_per_unit' => $costPerUnit,
                'date'          => $data['date'] ?? now()->toDateString(),
            ]);

            $newStock = $available - $data['qty'];
            if ($newStock <= $product->reorder_point) {
                CheckLowStockJob::dispatch($data['product_id'], $data['warehouse_id'], $newStock);
            }

            return $movement->load(['product', 'warehouse']);
        });
    }

    public function transfer(array $data, int $companyId): array
    {
        return DB::transaction(function () use ($data, $companyId) {
            $productId = $data['product_id'];
            $fromId    = $data['from_warehouse_id'];
            $toId      = $data['to_warehouse_id'];
            $qty       = $data['qty'];

            if ($fromId === $toId) {
                throw ValidationException::withMessages([
                    'to_warehouse_id' => 'Source and destination warehouses must differ.',
                ]);
            }

            $product   = $this->products->findOrFail($productId);
            $available = $product->currentStock($fromId);

            if ($available < $qty) {
                throw ValidationException::withMessages([
                    'qty' => "Insufficient stock in source warehouse. Available: {$available}.",
                ]);
            }

            $cogsTotal   = $this->reduceLayers($productId, $fromId, $qty);
            $costPerUnit = $qty > 0 ? intdiv($cogsTotal, $qty) : 0;
            $date        = $data['date'] ?? now()->toDateString();

            $out = $this->movements->create([
                'company_id'    => $companyId,
                'product_id'    => $productId,
                'warehouse_id'  => $fromId,
                'type'          => 'transfer',
                'qty'           => $qty,
                'cost_per_unit' => $costPerUnit,
                'date'          => $date,
            ]);

            $in = $this->movements->create([
                'company_id'    => $companyId,
                'product_id'    => $productId,
                'warehouse_id'  => $toId,
                'type'          => 'transfer',
                'qty'           => $qty,
                'cost_per_unit' => $costPerUnit,
                'date'          => $date,
            ]);

            StockLayer::create([
                'company_id'    => $companyId,
                'product_id'    => $productId,
                'warehouse_id'  => $toId,
                'qty_remaining' => $qty,
                'cost_per_unit' => $costPerUnit,
                'date'          => $date,
            ]);

            return [
                $out->load(['product', 'warehouse']),
                $in->load(['product', 'warehouse']),
            ];
        });
    }

    public function levels(): array
    {
        return StockLayer::with(['product', 'warehouse'])
            ->select('product_id', 'warehouse_id', DB::raw('SUM(qty_remaining) as qty'))
            ->groupBy('product_id', 'warehouse_id')
            ->having('qty', '>', 0)
            ->get()
            ->map(fn ($row) => [
                'product'   => $row->product,
                'warehouse' => $row->warehouse,
                'qty'       => (int) $row->qty,
            ])
            ->all();
    }

    public function lowStock(): array
    {
        return StockLayer::with(['product', 'warehouse'])
            ->select('product_id', 'warehouse_id', DB::raw('SUM(qty_remaining) as qty'))
            ->groupBy('product_id', 'warehouse_id')
            ->get()
            ->filter(fn ($row) => (int) $row->qty <= $row->product->reorder_point)
            ->map(fn ($row) => [
                'product'       => $row->product,
                'warehouse'     => $row->warehouse,
                'qty'           => (int) $row->qty,
                'reorder_point' => $row->product->reorder_point,
            ])
            ->values()
            ->all();
    }

    // ── FIFO helpers ──────────────────────────────────────────────────────────

    private function reduceLayers(int $productId, int $warehouseId, int $qty): int
    {
        $remaining = $qty;
        $totalCost = 0;

        $layers = StockLayer::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('qty_remaining', '>', 0)
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        foreach ($layers as $layer) {
            if ($remaining <= 0) {
                break;
            }
            $take       = min($remaining, $layer->qty_remaining);
            $totalCost += $take * $layer->cost_per_unit;
            $layer->decrement('qty_remaining', $take);
            $remaining -= $take;
        }

        return $totalCost;
    }
}
