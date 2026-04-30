import apiClient from '../../shared/api/client.api'
import type {
  OrderDetail,
  OrderType,
  OrdersQueryParams,
  OrdersResponse,
} from './orders.types'

export async function getOrdersOverview(
  params: OrdersQueryParams,
): Promise<OrdersResponse> {
  const response = await apiClient.get<OrdersResponse>('/orders', {
    params,
  })

  return response.data
}

export async function getOrderDetail(
  type: OrderType,
  id: number,
): Promise<OrderDetail> {
  const response = await apiClient.get<OrderDetail>(`/orders/${type}/${id}`)
  return response.data
}