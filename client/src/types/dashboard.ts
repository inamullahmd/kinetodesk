export type RevenuePoint = {
  month: string
  revenue: number | string
}

export type ProfitPoint = {
  month: string
  profit: number | string
}

export type QuarterRevenuePoint = {
  quarter: string
  revenue: number | string
}

export type QuarterProfitPoint = {
  quarter: string
  profit: number | string
}

export type RevenueComparison = {
  current_month_revenue: number | string
  previous_month_revenue: number | string
  percentage_change: number | null
}

export type QuarterComparison = {
  current_quarter_revenue: number | string
  previous_quarter_revenue: number | string
  percentage_change: number | null
}

export type ProfitComparison = {
  current_month_profit: number | string
  previous_month_profit: number | string
  percentage_change: number | null
}

export type QuarterProfitComparison = {
  current_quarter_profit: number | string
  previous_quarter_profit: number | string
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

export type RefundSummary = {
  refund_count: number
  refund_value: number | string
}

export type DashboardResponse = {
  asOfDate: string

  revenueComparison: RevenueComparison
  quarterComparison: QuarterComparison
  profitComparison: ProfitComparison
  quarterProfitComparison: QuarterProfitComparison
  currentInventoryValue: number | string

  last12MonthsRevenue: RevenuePoint[]
  last12MonthsProfit: ProfitPoint[]
  last8QuartersRevenue: QuarterRevenuePoint[]
  last8QuartersProfit: QuarterProfitPoint[]

  salesByChannel: SalesByChannelItem[]
  lowStockProducts: LowStockProduct[]

  topSellingProduct: TopSellingProduct | null
  topEmployee: TopEmployee | null
  pendingOrdersCount: number
  refundSummary: RefundSummary
}