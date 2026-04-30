export type CustomerType = 'individual' | 'business'

export type CustomerSummary = {
  totalCustomers: number
  repeatCustomers: number
  customerRevenue: number | string
  averageOrderValue: number | string
  averageClv: number | string
  clvAssumptionYears: number
}

export type CustomerStateDistribution = {
  state: string
  stateCode: string
  customerCount: number
  orderCount: number
  revenue: number | string
}

export type CustomerRow = {
  id: number
  type: CustomerType
  displayName: string
  contactName?: string | null
  email?: string | null
  phone?: string | null
  addressLine1?: string | null
  addressLine2?: string | null
  city?: string | null
  state?: string | null
  stateCode?: string | null
  postalCode?: string | null
  country?: string | null
  status: 'active' | 'inactive'
  createdAt?: string | null
  orderCount: number
  totalRevenue: number | string
  averageOrderValue: number | string
  firstOrderAt?: string | null
  lastOrderAt?: string | null
}

export type CustomerPagination = {
  currentPage: number
  perPage: number
  total: number
  lastPage: number
  from: number
  to: number
}

export type CustomerFilterOptions = {
  states: Array<{
    code: string
    name: string
  }>
  statuses: string[]
}

export type CustomersResponse = {
  type: CustomerType
  summary: CustomerSummary
  stateDistribution: CustomerStateDistribution[]
  customers: CustomerRow[]
  pagination: CustomerPagination
  filterOptions: CustomerFilterOptions
}

export type CustomerDetailMetrics = {
  orderCount: number
  totalRevenue: number | string
  averageOrderValue: number | string
  firstOrderAt?: string | null
  lastOrderAt?: string | null
}

export type CustomerRecentOrder = {
  id: number
  orderNumber: string
  channel?: string | null
  status: string
  paymentStatus?: string | null
  orderedAt?: string | null
  totalValue: number | string
}

export type CustomerTopProduct = {
  id: number
  title: string
  quantity: number
  totalValue: number | string
}

export type CustomerFavoriteChannel = {
  channel: string
  orderCount: number
  revenue: number | string
}

export type CustomerDetailResponse = {
  customer: CustomerRow
  metrics: CustomerDetailMetrics
  recentOrders: CustomerRecentOrder[]
  topProduct?: CustomerTopProduct | null
  favoriteChannel?: CustomerFavoriteChannel | null
}

export type CustomersQueryParams = {
  customerType: CustomerType
  search?: string
  state?: string
  status?: string
  page?: number
  perPage?: number
}