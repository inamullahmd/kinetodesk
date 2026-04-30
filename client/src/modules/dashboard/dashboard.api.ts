import apiClient from '../../shared/api/client.api'
import type { DashboardResponse } from './dashboard.types'

export async function getDashboardOverview() {
  const response = await apiClient.get<DashboardResponse>('/dashboard')
  return response.data
}