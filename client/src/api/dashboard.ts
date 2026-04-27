import apiClient from './client'
import type { DashboardResponse } from '../types/dashboard'

export async function getDashboardOverview() {
  const response = await apiClient.get<DashboardResponse>('/dashboard')
  return response.data
}