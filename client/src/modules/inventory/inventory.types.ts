export type InventoryPagination = {
  currentPage: number
  perPage: number
  total: number
  lastPage: number
  from: number
  to: number
}

export type InventoryOption = {
  id: number
  name: string
}

export type InventoryFilterOptions = {
  categories: InventoryOption[]
  brands: InventoryOption[]
  statuses: string[]
  serializedOptions: string[]
  alertTypes?: string[]
}

export type InventoryQueryParams = {
  startDate?: string
  endDate?: string
  search?: string
  statuses?: string[]
  alertTypes?: string[]
  category?: string
  brand?: string
  serialized?: string
  page?: number
  perPage?: number
  sort?: string
}

export type InventoryProductRow = {
  id: number
  title: string
  sku?: string | null
  modelNumber?: string | null
  brandName?: string | null
  categoryName?: string | null
  subCategoryName?: string | null
  isSerialized: boolean
  isActive: boolean
  currentStock: number
  inventoryValue: number | string
  avgUnitCost: number | string
  lastReceivedAt?: string | null
  lastSoldAt?: string | null
  status: string
  alertType?: string | null
}

export type InventoryProductsResponse = {
  rows: InventoryProductRow[]
  pagination: InventoryPagination
  filterOptions: InventoryFilterOptions
}

export type InventoryProductsQueryParams = InventoryQueryParams

export type InventoryAlertsSummary = {
  outOfStock: number
  lowStock: number
  staleStock: number
}

export type InventoryAlertsResponse = {
  summary: InventoryAlertsSummary
  rows: InventoryProductRow[]
  pagination: InventoryPagination
  filterOptions: InventoryFilterOptions
}

export type InventoryRecentMovement = {
  id: number
  movedAt?: string | null
  movementType: string
  qtyChange: number
  unitCost?: number | string | null
  referenceType?: string | null
  referenceId?: number | string | null
  batchCode?: string | null
  productTitle: string
  sku?: string | null
  modelNumber?: string | null
  brandName?: string | null
}

export type InventoryAlertsQueryParams = InventoryQueryParams

export type InventoryOverviewSummary = {
  totalProducts: number
  activeSkus: number
  inventoryValue: number | string
  lowStockItems: number
  outOfStock: number
  serializedUnits: number

  // aliases for compatibility
  productCount?: number
  activeProducts?: number
  lowStock?: number
  stockUnits?: number
}

export type InventoryCategoryBreakdown = {
  categoryName: string
  productCount: number
  stockQty: number
  inventoryValue: number | string

  // aliases for compatibility
  category?: string
  stockUnits?: number
}

export type InventoryOverviewResponse = {
  summary: InventoryOverviewSummary
  categoryBreakdown: InventoryCategoryBreakdown[]
  lowStockProducts: InventoryProductRow[]
  recentMovements: InventoryRecentMovement[]
}

export type StockBatchRow = {
  id: number
  batchCode?: string | null
  receivedAt?: string | null
  qtyReceived: number
  qtyRemaining: number
  unitCost: number | string
  remainingValue: number | string
  purchaseOrderNumber?: string | null
}

export type StockMovementRow = {
  id: number
  movedAt?: string | null
  movementType: string
  qtyChange: number
  unitCost?: number | string | null
  referenceType?: string | null
  referenceId?: number | string | null
  batchCode?: string | null
}

export type ProductSerialRow = {
  id: number
  serialNumber: string
  status: string
  receivedAt?: string | null
  batchCode?: string | null
}

export type InventoryProductDetail = InventoryProductRow & {
  stockQty: number
  batches: StockBatchRow[]
  movements: StockMovementRow[]
  serials: ProductSerialRow[]
}