export type OverviewKpis = {
  total_revenue: number;
  total_orders: number;
  average_order_value: number;
  units_sold: number;
  inventory_value: number;
  low_stock_count: number;
  purchase_spend: number;
};

export type RevenueTrendPoint = {
  date: string;
  revenue: number;
};

export type LowStockItem = {
  product_id: number;
  product_name: string;
  sku: string;
  stock_on_hand: number;
  reorder_level: number;
};

export type RecentSale = {
  order_number: string;
  customer_name: string;
  product_name: string;
  quantity: number;
  unit_price: number;
  total_amount: number;
  status: string;
  sold_at: string;
};

export type StatusPoint = {
  name: string;
  value: number;
};

export type TopProductPoint = {
  name: string;
  revenue: number;
};

export type CategorySalesPoint = {
  name: string;
  value: number;
};

export type RegionSalesPoint = {
  name: string;
  value: number;
};

export type CustomerTypeSalesPoint = {
  name: string;
  value: number;
};

export type LowStockCategoryPoint = {
  name: string;
  value: number;
};

export type DateRangeOption = '1m' | '3m' | '6m' | '12m' | 'custom';