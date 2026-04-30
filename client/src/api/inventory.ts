import apiClient from './client'
import type {
  InventoryAlertsResponse,
  InventoryOverviewResponse,
  InventoryProductDetail,
  InventoryProductsResponse,
  InventoryQueryParams,
} from '../types/inventory'

export async function getInventoryOverview(
  params: Pick<InventoryQueryParams, 'startDate' | 'endDate'>,
): Promise<InventoryOverviewResponse> {
  const response = await apiClient.get<InventoryOverviewResponse>(
    '/inventory/overview',
    {
      params,
    },
  )

  return response.data
}

export async function getInventoryProducts(
  params: InventoryQueryParams,
): Promise<InventoryProductsResponse> {
  const response = await apiClient.get<InventoryProductsResponse>(
    '/inventory/products',
    {
      params,
    },
  )

  return response.data
}

export async function getInventoryProductDetail(
  id: number,
): Promise<InventoryProductDetail> {
  const response = await apiClient.get<InventoryProductDetail>(
    `/inventory/products/${id}`,
  )

  return response.data
}

export async function getInventoryAlerts(
  params: InventoryQueryParams,
): Promise<InventoryAlertsResponse> {
  const response = await apiClient.get<InventoryAlertsResponse>(
    '/inventory/alerts',
    {
      params,
    },
  )

  return response.data
}