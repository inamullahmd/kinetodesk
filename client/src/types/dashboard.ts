export type RevenuePoint = {
  month: string
  revenue: number | string
}

export type RevenueComparison = {
  current_month_revenue: number | string
  previous_month_revenue: number | string
  percentage_change: number | null
}

export type QuarterComparison = {
  current_quarter_start: string
  current_quarter_end: string
  previous_quarter_start: string
  previous_quarter_end: string
  current_quarter_revenue: number | string
  previous_quarter_revenue: number | string
  percentage_change: number | null
}

export type SalesByChannelItem = {
  channel: string | null
  total_revenue: number | string
  total_orders: number | string
}

export type TopSellingProduct = {
  product_id: number
  product_name: string
  total_quantity: number | string
}

export type TopEmployee = {
  employee_id: number
  employee_name: string
  total_sales: number | string
  total_orders: number | string
}

export type OrdersByStatusItem = {
  status: string
  total_orders: number | string
}

export type LowStockProduct = {
  id: number
  title: string
  stock_qty: number
  stock_status: {
    label: string
    action: string
    tone: 'danger' | 'warning' | 'caution' | 'info'
  }
}

export type QuarterRevenuePoint = {
  quarter: string
  revenue: number | string
}

export type ProfitComparison = {
  current_month_start: string
  current_month_end: string
  previous_month_start: string
  previous_month_end: string
  current_month_profit: number | string
  previous_month_profit: number | string
  percentage_change: number | null
}

export type QuarterProfitComparison = {
  current_quarter_start: string
  current_quarter_end: string
  previous_quarter_start: string
  previous_quarter_end: string
  current_quarter_profit: number | string
  previous_quarter_profit: number | string
  percentage_change: number | null
}


export type ProfitPoint = {
  month: string
  profit: number | string
}

export type QuarterProfitPoint = {
  quarter: string
  profit: number | string
}

export type StateCount = {
  state: string
  count: number
}


export type DashboardResponse = {
  asOfDate: string

  last12MonthsRevenue: RevenuePoint[]
  last8QuartersRevenue: QuarterRevenuePoint[]

  last12MonthsProfit: ProfitPoint[]
  last8QuartersProfit: QuarterProfitPoint[]

  revenueComparison: RevenueComparison
  quarterComparison: QuarterComparison

  profitComparison: ProfitComparison
  quarterProfitComparison: QuarterProfitComparison

  currentInventoryValue: number | string

  salesByChannel: SalesByChannelItem[]
  topSellingProducts: TopSellingProduct[]
  topEmployees: TopEmployee[]
  ordersByStatus: OrdersByStatusItem[]
  lowStockProducts: LowStockProduct[]
  customerBusinessMap: {
    customers: StateCount[]
    businesses: StateCount[]
  }
}