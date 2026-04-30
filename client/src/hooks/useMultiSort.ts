import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { serializeSorts, type SortItem, type SortState } from '../types/sort'

export function useMultiSort(
  initialSorts: SortState = [],
  options?: {
    onChange?: (nextSorts: SortState) => void
  },
) {
  const [sorts, setSortsState] = useState<SortState>(initialSorts)
  const onChangeRef = useRef(options?.onChange)

  useEffect(() => {
    onChangeRef.current = options?.onChange
  }, [options?.onChange])

  const setSorts = useCallback(
    (nextSorts: SortState | ((currentSorts: SortState) => SortState)) => {
      setSortsState((currentSorts) => {
        const resolvedSorts =
          typeof nextSorts === 'function'
            ? nextSorts(currentSorts)
            : nextSorts

        onChangeRef.current?.(resolvedSorts)

        return resolvedSorts
      })
    },
    [],
  )

  const cycleSort = useCallback(
    (field: string) => {
      setSorts((currentSorts) => {
        const existingSort = currentSorts.find((sort) => sort.field === field)

        if (!existingSort) {
          return [...currentSorts, { field, direction: 'asc' }]
        }

        if (existingSort.direction === 'asc') {
          return currentSorts.map((sort) =>
            sort.field === field ? { ...sort, direction: 'desc' } : sort,
          )
        }

        return currentSorts.filter((sort) => sort.field !== field)
      })
    },
    [setSorts],
  )

  const clearSorts = useCallback(() => {
    setSorts([])
  }, [setSorts])

  const getSort = useCallback(
    (field: string): SortItem | undefined =>
      sorts.find((sort) => sort.field === field),
    [sorts],
  )

  const getSortIndex = useCallback(
    (field: string): number | undefined => {
      const index = sorts.findIndex((sort) => sort.field === field)
      return index >= 0 ? index : undefined
    },
    [sorts],
  )

  const sortParam = useMemo(() => serializeSorts(sorts), [sorts])

  return {
    sorts,
    setSorts,
    cycleSort,
    clearSorts,
    getSort,
    getSortIndex,
    sortParam,
  }
}
