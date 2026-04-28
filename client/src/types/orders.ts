export type OrderStatusCount = {
  status: string
  total_orders: number
}

export type OrderChannelCount = {
  channel: string | null
  total_orders: number
}

export type OrdersTrendPoint = {
  date: string
  label: string
  total_orders: number
}

export type OrdersStatsResponse = {
  summary: {
    total_orders: number
    pending_orders: number
    delivered_orders: number
    cancelled_orders: number
    total_value: number
    refund_count: number
    refund_value: number
  }
  summary_period: {
    label: string
    start_date: string
    end_date: string
  }
  status_counts: OrderStatusCount[]
  channel_counts: OrderChannelCount[]
  trend: OrdersTrendPoint[]
}

export type OrderListRow = {
  id: number
  order_number: string
  ordered_at: string | null
  customer_name: string
  channel: string | null
  status: string
  grand_total: number
  employee_name: string
}

export type OrdersListResponse = {
  data: OrderListRow[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
  }
}

export type OrdersFilters = {
  search?: string
  status?: string
  channel?: string
  date_from?: string
  date_to?: string
  page?: number
  per_page?: number
  sort?: string
}