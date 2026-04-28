import apiClient from './client'
import type {
  OrdersFilters,
  OrdersListResponse,
  OrdersStatsResponse,
} from '../types/orders'

export async function getOrdersStats(params: OrdersFilters = {}) {
  const response = await apiClient.get<OrdersStatsResponse>('/orders/stats', {
    params,
  })

  return response.data
}

export async function getOrders(params: OrdersFilters = {}) {
  const response = await apiClient.get<OrdersListResponse>('/orders', {
    params,
  })

  return response.data
}