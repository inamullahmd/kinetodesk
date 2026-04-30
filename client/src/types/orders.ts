export type OrderType = 'sales' | 'purchase'

export type OrdersReportingPeriod = {
  startDate: string
  endDate: string
  maxDate: string
  label: string
}

export type OrdersSummary = {
  totalOrders: number
  openOrders: number
  completedOrders: number
  totalValue: number | string
  attentionOrders: number
}

export type SupplierOption = {
  id: number
  name: string
}

export type OrdersFilterOptions = {
  statuses: string[]
  channels: string[]
  suppliers: SupplierOption[]
}

export type OrdersPagination = {
  currentPage: number
  perPage: number
  total: number
  lastPage: number
  from: number
  to: number
}

export type RecentOrder = {
  id: number
  type: OrderType
  orderNumber: string
  counterpartyName: string
  counterpartyMeta?: string | null
  date?: string | null
  status: string
  paymentStatus?: string | null
  channel?: string | null
  expectedAt?: string | null
  receivedAt?: string | null
  itemCount: number
  totalQuantity: number
  totalValue: number | string
}

export type OrdersResponse = {
  type: OrderType
  reportingPeriod: OrdersReportingPeriod
  summary: OrdersSummary
  recentOrders: RecentOrder[]
  pagination: OrdersPagination
  filterOptions: OrdersFilterOptions
}

export type OrdersQueryParams = {
  type: OrderType
  startDate: string
  endDate: string
  search?: string
  statuses?: string[]
  channels?: string[]
  suppliers?: string[]
  page?: number
  perPage?: number
}

export type OrderDetailTotals = {
  subtotal: number | string
  discountAmount: number | string
  taxAmount: number | string
  shippingAmount: number | string
  otherAmount: number | string
  grandTotal: number | string
}

export type OrderDetailAddress = {
  line1?: string | null
  line2?: string | null
  city?: string | null
  state?: string | null
  postalCode?: string | null
  country?: string | null
}

export type SalesOrderDetailItem = {
  id: number
  productId: number
  productTitle: string
  sku?: string | null
  modelNumber?: string | null
  brandName?: string | null
  quantity: number
  unitPrice: number | string
  discountAmount: number | string
  finalUnitPrice: number | string
  costBasis: number | string
  minAllowedPrice: number | string
  commissionPerUnit: number | string
  commissionTotal: number | string
  lineSubtotal: number | string
  lineTotal: number | string
  lineProfit: number | string
}

export type PurchaseOrderDetailItem = {
  id: number
  productId: number
  productTitle: string
  sku?: string | null
  modelNumber?: string | null
  brandName?: string | null
  quantity: number
  unitCost: number | string
  lineTotal: number | string
}

export type OrderDetail = {
  type: OrderType
  id: number
  orderNumber: string
  status: string
  paymentStatus?: string | null
  channel?: string | null
  orderedAt?: string | null
  completedAt?: string | null
  expectedAt?: string | null
  receivedAt?: string | null
  counterpartyName: string
  counterpartyEmail?: string | null
  counterpartyPhone?: string | null
  ownerName?: string | null
  address?: OrderDetailAddress
  totals: OrderDetailTotals
  notes?: string | null
  items: Array<SalesOrderDetailItem | PurchaseOrderDetailItem>
}