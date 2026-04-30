export type LookupEntity = 'sales-orders' | 'purchase-orders' | 'customers' | 'inventory'

export type LookupResultType = 'sales' | 'purchase' | 'customer' | 'inventory'

export type LookupResult = {
  entity: LookupEntity
  type: LookupResultType
  id: number
  label: string
  title: string
  subtitle?: string | null
  status?: string | null
  amount?: number | null
  date?: string | null
  meta?: string | null
  detailEndpoint: string
}

export type LookupResponse = {
  entity: LookupEntity
  query: string
  results: LookupResult[]
}