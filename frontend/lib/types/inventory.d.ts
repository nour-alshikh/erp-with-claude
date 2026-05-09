export interface Product {
  id: number;
  name: string;
  sku: string;
  barcode: string | null;
  unit_of_measure: string;
  reorder_point: number;
  cost_price: number;    // cents
  selling_price: number; // cents
}

export interface Warehouse {
  id: number;
  name: string;
  location: string | null;
}

export interface StockMovement {
  id: number;
  product_id: number;
  warehouse_id: number;
  type: 'in' | 'out' | 'transfer';
  qty: number;
  cost_per_unit: number; // cents
  date: string;
}

export interface StockLevel {
  product_id: number;
  warehouse_id: number;
  qty: number;
  product: Product;
  warehouse: Warehouse;
}
