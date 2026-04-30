import apiClient from '../../shared/api/client.api'
import type {
  CustomerDetailResponse,
  CustomersQueryParams,
  CustomersResponse,
  EmployeeDetailResponse,
  EmployeesQueryParams,
  EmployeesResponse,
  SupplierDetailResponse,
  SuppliersQueryParams,
  SuppliersResponse,
} from './directory.types'

export async function getCustomers(
  params: CustomersQueryParams,
): Promise<CustomersResponse> {
  const response = await apiClient.get<CustomersResponse>('/customers', {
    params,
  })

  return response.data
}

export async function getCustomerDetail(
  id: number,
): Promise<CustomerDetailResponse> {
  const response = await apiClient.get<CustomerDetailResponse>(`/customers/${id}`)
  return response.data
}

export async function getSuppliers(
  params: SuppliersQueryParams,
): Promise<SuppliersResponse> {
  const response = await apiClient.get<SuppliersResponse>('/suppliers', {
    params,
  })

  return response.data
}

export async function getSupplierDetail(
  id: number,
): Promise<SupplierDetailResponse> {
  const response = await apiClient.get<SupplierDetailResponse>(`/suppliers/${id}`)
  return response.data
}

export async function getEmployees(
  params: EmployeesQueryParams,
): Promise<EmployeesResponse> {
  const response = await apiClient.get<EmployeesResponse>('/employees', {
    params,
  })

  return response.data
}

export async function getEmployeeDetail(
  id: number,
): Promise<EmployeeDetailResponse> {
  const response = await apiClient.get<EmployeeDetailResponse>(`/employees/${id}`)
  return response.data
}