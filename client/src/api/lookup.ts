import api from './client'
import type { LookupEntity, LookupResponse } from '../types/lookup'

export async function searchLookup(params: {
  entity: LookupEntity
  q: string
  limit?: number
}): Promise<LookupResponse> {
  const { data } = await api.get<LookupResponse>('/lookup', { params })
  return data
}