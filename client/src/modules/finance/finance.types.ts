export type FinanceOverviewSummary = {
  revenue: number | string
  grossProfit: number | string
  grossMargin: number
  orderCount: number
  unitsSold: number
  averageOrderValue: number | string
  taxCollected: number | string
  discountsGiven: number | string
  shippingCharged: number | string
  paymentsCollected: number | string
  paymentRefunds: number | string
  returnRefunds: number | string
  returnCount: number
  totalRefunds: number | string
  refundRecordCount: number
  pendingReceivables: number | string
  earnedCommission: number | string
  pendingCommission: number | string
  paidCommission: number | string
}

export type FinanceDailyTrendRow = {
  date: string
  label: string
  revenue: number | string
  grossProfit: number | string
  payments: number | string
  orders: number
}

export type PaymentStatusBreakdownRow = {
  status: string
  orderCount: number
  totalValue: number | string
}

export type PaymentMethodBreakdownRow = {
  method: string
  paymentCount: number
  totalAmount: number | string
}

export type RecentPaymentRow = {
  id: number
  paidAt?: string | null
  salesOrderNumber: string
  customerName: string
  method: string
  status: string
  amount: number | string
  transactionReference?: string | null
}

export type RecentRefundRow = {
  id: number
  createdAt?: string | null
  returnNumber: string
  salesOrderNumber: string
  customerName: string
  status: string
  reason?: string | null
  refundAmount: number | string
  source?: 'return' | string
}

export type CommissionPayoutRow = {
  id: number
  periodStart?: string | null
  periodEnd?: string | null
  employeeNumber: string
  employeeName: string
  status: string
  paidAt?: string | null
  totalCommission: number | string
}

export type OpenReceivableRow = {
  id: number
  orderedAt?: string | null
  salesOrderNumber: string
  customerName: string
  status: string
  channel: string
  amountDue: number | string
}

export type FinanceOverviewResponse = {
  dateRange: {
    startDate: string
    endDate: string
  }
  summary: FinanceOverviewSummary
  dailyTrend: FinanceDailyTrendRow[]
  paymentStatusBreakdown: PaymentStatusBreakdownRow[]
  paymentMethodBreakdown: PaymentMethodBreakdownRow[]
  recentPayments: RecentPaymentRow[]
  recentRefunds: RecentRefundRow[]
  commissionPayouts: CommissionPayoutRow[]
  openReceivables: OpenReceivableRow[]
}

export type FinanceOverviewQueryParams = {
  startDate?: string
  endDate?: string
}