export type SortDirection = 'asc' | 'desc'

export type SortItem = {
  field: string
  direction: SortDirection
}

export type SortState = SortItem[]

export function serializeSorts(sorts: SortState): string {
  return sorts.map((sort) => `${sort.field}:${sort.direction}`).join(',')
}