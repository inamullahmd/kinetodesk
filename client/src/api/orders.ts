import apiClient from './client'
import type {
  OrderDetail,
  OrderType,
  OrdersQueryParams,
  OrdersResponse,
} from '../types/orders'

export async function getOrdersOverview(params: OrdersQueryParams) {
  const response = await apiClient.get<OrdersResponse>('/orders', {
    params,
  })

  return response.data
}

export async function getOrderDetail(type: OrderType, id: number) {
  const response = await apiClient.get<OrderDetail>(`/orders/${type}/${id}`)

  return response.data
}