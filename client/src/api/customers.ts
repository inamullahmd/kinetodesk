import apiClient from './client'
import type {
  CustomerDetailResponse,
  CustomersQueryParams,
  CustomersResponse,
} from '../types/customers'

export async function getCustomers(params: CustomersQueryParams) {
  const response = await apiClient.get<CustomersResponse>('/customers', {
    params,
  })

  return response.data
}

export async function getCustomerDetail(id: number) {
  const response = await apiClient.get<CustomerDetailResponse>(`/customers/${id}`)

  return response.data
}