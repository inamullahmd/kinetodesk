import apiClient from './client'
import type {
  InventoryAlertsResponse,
  InventoryOverviewResponse,
  InventoryProductDetail,
  InventoryProductsResponse,
  InventoryQueryParams,
} from '../types/inventory'

export async function getInventoryOverview(params: Pick<InventoryQueryParams, 'startDate' | 'endDate'>) {
  const response = await apiClient.get<InventoryOverviewResponse>('/inventory/overview', {
    params,
  })

  return response.data
}

export async function getInventoryProducts(params: InventoryQueryParams) {
  const response = await apiClient.get<InventoryProductsResponse>('/inventory/products', {
    params,
  })

  return response.data
}

export async function getInventoryProductDetail(id: number) {
  const response = await apiClient.get<InventoryProductDetail>(`/inventory/products/${id}`)

  return response.data
}

export async function getInventoryAlerts(params: InventoryQueryParams) {
  const response = await apiClient.get<InventoryAlertsResponse>('/inventory/alerts', {
    params,
  })

  return response.data
}