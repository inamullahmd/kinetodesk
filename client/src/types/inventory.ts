export type InventoryReportingPeriod = {
  startDate: string
  endDate: string
  maxDate: string
  label: string
}

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

export type InventorySummary = {
  totalProducts: number
  activeSkus: number
  inventoryValue: number | string
  lowStockItems: number
  outOfStockItems: number
  serializedUnits: number
}

export type InventoryAlertSummary = {
  outOfStock: number
  lowStock: number
  staleStock: number
}

export type InventoryProductRow = {
  id: number
  title: string
  sku: string
  modelNumber?: string | null
  brandName?: string | null
  categoryName?: string | null
  subCategoryName?: string | null
  isSerialized: boolean
  isActive: boolean
  stockQty: number
  inventoryValue: number | string
  avgUnitCost: number | string
  lastReceivedAt?: string | null
  lastSoldAt?: string | null
  daysSinceLastSale?: number | null
  status: 'in_stock' | 'low_stock' | 'out_of_stock'
}

export type InventoryBatchRow = {
  id: number
  batchCode?: string | null
  qtyReceived: number
  qtyRemaining: number
  unitCost: number | string
  remainingValue: number | string
  receivedAt?: string | null
  purchaseOrderNumber?: string | null
}

export type InventoryMovementRow = {
  id: number
  movementType: string
  qtyChange: number
  unitCost?: number | string | null
  movedAt?: string | null
  referenceType?: string | null
  referenceId?: number | string | null
  productTitle?: string
  sku?: string
  batchCode?: string | null
}

export type InventorySerialRow = {
  id: number
  serialNumber: string
  status: string
  batchCode?: string | null
  receivedAt?: string | null
}

export type InventoryProductDetail = InventoryProductRow & {
  batches: InventoryBatchRow[]
  movements: InventoryMovementRow[]
  serials: InventorySerialRow[]
}

export type CategoryBreakdownRow = {
  categoryName: string
  productCount: number
  stockQty: number
  inventoryValue: number | string
  outOfStockCount: number
  lowStockCount: number
}

export type InventoryOverviewResponse = {
  reportingPeriod: InventoryReportingPeriod
  summary: InventorySummary
  categoryBreakdown: CategoryBreakdownRow[]
  recentMovements: InventoryMovementRow[]
  alertsPreview: InventoryProductRow[]
}

export type InventoryProductsResponse = {
  reportingPeriod: InventoryReportingPeriod
  rows: InventoryProductRow[]
  pagination: InventoryPagination
  filterOptions: InventoryFilterOptions
}

export type InventoryAlertsResponse = {
  reportingPeriod: InventoryReportingPeriod
  summary: InventoryAlertSummary
  rows: InventoryProductRow[]
  pagination: InventoryPagination
  filterOptions: InventoryFilterOptions
}

export type InventoryQueryParams = {
  startDate: string
  endDate: string
  search?: string
  statuses?: string[]
  alertTypes?: string[]
  category?: string
  brand?: string
  serialized?: string
  page?: number
  perPage?: number
}