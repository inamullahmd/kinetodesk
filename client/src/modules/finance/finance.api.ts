import apiClient from '../../shared/api/client.api'
import type {
  FinanceOverviewQueryParams,
  FinanceOverviewResponse,
} from './finance.types'

export async function getFinanceOverview(
  params: FinanceOverviewQueryParams = {},
): Promise<FinanceOverviewResponse> {
  const response = await apiClient.get<FinanceOverviewResponse>('/finance/overview', {
    params,
  })

  return response.data
}