import apiClient from '../../shared/api/client.api'
import type {
  InventoryAlertsQueryParams,
  InventoryAlertsResponse,
  InventoryMovementsQueryParams,
  InventoryMovementsResponse,
  InventoryOverviewResponse,
  InventoryProductDetail,
  InventoryProductsQueryParams,
  InventoryProductsResponse,
  InventoryQueryParams,
} from './inventory.types'

export async function getInventoryOverview(
  params: Pick<InventoryQueryParams, 'startDate' | 'endDate'> = {},
): Promise<InventoryOverviewResponse> {
  const response = await apiClient.get<InventoryOverviewResponse>('/inventory/overview', {
    params,
  })

  return response.data
}

export async function getInventoryProducts(
  params: InventoryProductsQueryParams,
): Promise<InventoryProductsResponse> {
  const response = await apiClient.get<InventoryProductsResponse>('/inventory/products', {
    params,
  })

  return response.data
}

export async function getInventoryAlerts(
  params: InventoryAlertsQueryParams,
): Promise<InventoryAlertsResponse> {
  const response = await apiClient.get<InventoryAlertsResponse>('/inventory/alerts', {
    params,
  })

  return response.data
}

export async function getInventoryMovements(
  params: InventoryMovementsQueryParams,
): Promise<InventoryMovementsResponse> {
  const response = await apiClient.get<InventoryMovementsResponse>('/inventory/movements', {
    params,
  })

  return response.data
}

export async function getInventoryProductDetail(
  id: number,
  params: Pick<InventoryQueryParams, 'startDate' | 'endDate'> = {},
): Promise<InventoryProductDetail> {
  const response = await apiClient.get<InventoryProductDetail>(
    `/inventory/products/${id}`,
    {
      params,
    },
  )

  return response.data
}