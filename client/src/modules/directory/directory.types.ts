export type DirectoryStatus = 'active' | 'inactive'

export type DirectoryPagination = {
  currentPage: number
  perPage: number
  total: number
  lastPage: number
  from: number
  to: number
}

export type DirectoryStateOption = {
  code: string
  name: string
}

export type SupplierSummary = {
  totalSuppliers: number
  activeSuppliers: number
  purchaseOrderCount: number
  openPurchaseOrders: number
  fulfilledPurchaseOrders: number
  totalSpend: number | string
  productCount: number
}

export type SupplierRow = {
  id: number
  name: string
  contactPerson?: string | null
  email?: string | null
  phone?: string | null
  website?: string | null
  taxNumber?: string | null
  addressLine1?: string | null
  addressLine2?: string | null
  city?: string | null
  state?: string | null
  postalCode?: string | null
  country?: string | null
  status: DirectoryStatus
  createdAt?: string | null
  purchaseOrderCount: number
  openPurchaseOrders: number
  fulfilledPurchaseOrders: number
  totalSpend: number | string
  averageOrderValue: number | string
  productCount: number
  preferredProductCount: number
  averageLeadTimeDays: number | string
  lastOrderAt?: string | null
  lastReceivedAt?: string | null
}

export type SupplierFilterOptions = {
  states: DirectoryStateOption[]
  statuses: DirectoryStatus[]
}

export type SuppliersResponse = {
  summary: SupplierSummary
  suppliers: SupplierRow[]
  pagination: DirectoryPagination
  filterOptions: SupplierFilterOptions
}

export type SuppliersQueryParams = {
  search?: string
  state?: string
  status?: string
  page?: number
  perPage?: number
  sort?: string
}

export type SupplierMetrics = {
  purchaseOrderCount: number
  openPurchaseOrders: number
  fulfilledPurchaseOrders: number
  totalSpend: number | string
  averageOrderValue: number | string
  productCount: number
  preferredProductCount: number
  averageLeadTimeDays: number | string
  lastOrderAt?: string | null
  lastReceivedAt?: string | null
}

export type SupplierRecentPurchaseOrder = {
  id: number
  orderNumber: string
  status: string
  orderedAt?: string | null
  expectedAt?: string | null
  receivedAt?: string | null
  totalCost: number | string
  itemCount: number
  totalQuantity: number
}

export type SupplierProductRow = {
  id: number
  title: string
  sku?: string | null
  modelNumber?: string | null
  brandName?: string | null
  categoryName?: string | null
  supplierSku?: string | null
  preferredSupplier: boolean
  minOrderQty: number
  leadTimeDays?: number | null
  lastCost: number | string
  currency?: string | null
  status: DirectoryStatus
}

export type SupplierDetailResponse = {
  supplier: SupplierRow
  metrics: SupplierMetrics
  recentPurchaseOrders: SupplierRecentPurchaseOrder[]
  products: SupplierProductRow[]
}

export type EmployeeSummary = {
  totalEmployees: number
  activeEmployees: number
  salesRepresentatives: number
  salesOrderCount: number
  unitsSold: number
  totalRevenue: number | string
  totalProfit: number | string
  totalCommission: number | string
  pendingCommission: number | string
}

export type EmployeeRow = {
  id: number
  employeeNumber: string
  firstName: string
  lastName: string
  displayName: string
  email: string
  phone?: string | null
  role: string
  status: DirectoryStatus
  createdAt?: string | null
  salesOrderCount: number
  unitsSold: number
  totalRevenue: number | string
  totalProfit: number | string
  totalCommission: number | string
  averageLineValue: number | string
  pendingCommission: number | string
  paidCommission: number | string
  lastSaleAt?: string | null
  lastPaidAt?: string | null
}

export type EmployeeFilterOptions = {
  roles: string[]
  statuses: DirectoryStatus[]
}

export type EmployeesResponse = {
  summary: EmployeeSummary
  employees: EmployeeRow[]
  pagination: DirectoryPagination
  filterOptions: EmployeeFilterOptions
}

export type EmployeesQueryParams = {
  search?: string
  role?: string
  status?: string
  page?: number
  perPage?: number
  sort?: string
}

export type EmployeeMetrics = {
  salesOrderCount: number
  unitsSold: number
  totalRevenue: number | string
  totalProfit: number | string
  totalCommission: number | string
  averageLineValue: number | string
  pendingCommission: number | string
  paidCommission: number | string
  lastSaleAt?: string | null
  lastPaidAt?: string | null
}

export type EmployeeRecentSalesOrder = {
  id: number
  orderNumber: string
  customerName?: string | null
  channel?: string | null
  status: string
  paymentStatus?: string | null
  orderedAt?: string | null
  quantity: number
  totalValue: number | string
  profit: number | string
  commission: number | string
}

export type EmployeeTopProduct = {
  id: number
  title: string
  sku?: string | null
  modelNumber?: string | null
  brandName?: string | null
  quantity: number
  totalValue: number | string
  profit: number | string
  commission: number | string
}

export type EmployeeCommissionPayout = {
  id: number
  periodStart?: string | null
  periodEnd?: string | null
  totalCommission: number | string
  status: string
  paidAt?: string | null
}

export type EmployeeDetailResponse = {
  employee: EmployeeRow
  metrics: EmployeeMetrics
  recentSalesOrders: EmployeeRecentSalesOrder[]
  topProducts: EmployeeTopProduct[]
  commissionPayouts: EmployeeCommissionPayout[]
}

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
  states: Array<{ code: string; name: string }>
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
  sort?: string
}