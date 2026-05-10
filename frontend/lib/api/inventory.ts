import client from './client';

export interface ProductPayload {
  name: string;
  sku?: string | null;
  barcode?: string | null;
  unit_of_measure?: string;
  reorder_point?: number;
  cost_price: number;
  selling_price: number;
}

export interface WarehousePayload {
  name: string;
  location?: string | null;
}

export interface StockInPayload {
  product_id: number;
  warehouse_id: number;
  qty: number;
  cost_per_unit: number;
  date?: string;
}

export interface StockOutPayload {
  product_id: number;
  warehouse_id: number;
  qty: number;
  date?: string;
}

export interface StockTransferPayload {
  product_id: number;
  from_warehouse_id: number;
  to_warehouse_id: number;
  qty: number;
  date?: string;
}

export const inventoryApi = {
  getProducts:   () => client.get('/inventory/products'),
  getProduct:    (id: number) => client.get(`/inventory/products/${id}`),
  createProduct: (data: ProductPayload) => client.post('/inventory/products', data),
  updateProduct: (id: number, data: Partial<ProductPayload>) =>
    client.put(`/inventory/products/${id}`, data),
  deleteProduct: (id: number) => client.delete(`/inventory/products/${id}`),

  getWarehouses:   () => client.get('/inventory/warehouses'),
  getWarehouse:    (id: number) => client.get(`/inventory/warehouses/${id}`),
  createWarehouse: (data: WarehousePayload) => client.post('/inventory/warehouses', data),
  updateWarehouse: (id: number, data: Partial<WarehousePayload>) =>
    client.put(`/inventory/warehouses/${id}`, data),
  deleteWarehouse: (id: number) => client.delete(`/inventory/warehouses/${id}`),

  getMovements: () => client.get('/inventory/stock'),
  stockIn:      (data: StockInPayload) => client.post('/inventory/stock/in', data),
  stockOut:     (data: StockOutPayload) => client.post('/inventory/stock/out', data),
  transfer:     (data: StockTransferPayload) => client.post('/inventory/stock/transfer', data),
  getLevels:    () => client.get('/inventory/stock/levels'),
  getLowStock:  () => client.get('/inventory/stock/low-stock'),
};
