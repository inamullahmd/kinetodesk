import { useMemo, useState } from 'react'
import { geoAlbersUsa, geoPath } from 'd3-geo'
import { feature } from 'topojson-client'
import statesTopoJson from 'us-atlas/states-10m.json'
import type { CustomerStateDistribution } from '../directory.types'
import { formatCurrency, formatNumber } from '../../../shared/utils/format'

type CustomerStateMapProps = {
  data: CustomerStateDistribution[]
  variant?: 'light' | 'dark'
}

type HoveredState = {
  stateName: string
  stateCode: string
  customerCount: number
  orderCount: number
  revenue: number | string
  x: number
  y: number
}

type GeoFeature = {
  id?: string | number
  properties?: {
    name?: string
  }
}

const MAP_WIDTH = 980
const MAP_HEIGHT = 500

const FIPS_TO_STATE_CODE: Record<string, string> = {
  '01': 'AL',
  '02': 'AK',
  '04': 'AZ',
  '05': 'AR',
  '06': 'CA',
  '08': 'CO',
  '09': 'CT',
  '10': 'DE',
  '11': 'DC',
  '12': 'FL',
  '13': 'GA',
  '15': 'HI',
  '16': 'ID',
  '17': 'IL',
  '18': 'IN',
  '19': 'IA',
  '20': 'KS',
  '21': 'KY',
  '22': 'LA',
  '23': 'ME',
  '24': 'MD',
  '25': 'MA',
  '26': 'MI',
  '27': 'MN',
  '28': 'MS',
  '29': 'MO',
  '30': 'MT',
  '31': 'NE',
  '32': 'NV',
  '33': 'NH',
  '34': 'NJ',
  '35': 'NM',
  '36': 'NY',
  '37': 'NC',
  '38': 'ND',
  '39': 'OH',
  '40': 'OK',
  '41': 'OR',
  '42': 'PA',
  '44': 'RI',
  '45': 'SC',
  '46': 'SD',
  '47': 'TN',
  '48': 'TX',
  '49': 'UT',
  '50': 'VT',
  '51': 'VA',
  '53': 'WA',
  '54': 'WV',
  '55': 'WI',
  '56': 'WY',
}

function getStateCodeFromGeoId(id: string | number | undefined) {
  if (id === undefined || id === null) return null

  const fips = String(id).padStart(2, '0')

  return FIPS_TO_STATE_CODE[fips] ?? null
}

function getFillOpacity(value: number, maxValue: number, isDark: boolean) {
  if (value <= 0 || maxValue <= 0) return 0

  const normalized = Math.sqrt(value / maxValue)

  if (isDark) {
    return Math.max(0.28, Math.min(0.82, normalized * 0.78))
  }

  return Math.max(0.16, Math.min(0.9, normalized * 0.9))
}

function getStateFill({
  customerCount,
  maxCustomers,
  isDark,
  isHovered,
}: {
  customerCount: number
  maxCustomers: number
  isDark: boolean
  isHovered: boolean
}) {
  if (isHovered) {
    return isDark ? '#a78bfa' : '#2563eb'
  }

  if (customerCount <= 0) {
    return isDark ? '#111827' : '#f1f5f9'
  }

  const opacity = getFillOpacity(customerCount, maxCustomers, isDark)

  return isDark
    ? `rgba(129, 140, 248, ${opacity})`
    : `rgba(37, 99, 235, ${opacity})`
}

export default function CustomerStateMap({
  data,
  variant = 'light',
}: CustomerStateMapProps) {
  const isDark = variant === 'dark'
  const [hoveredState, setHoveredState] = useState<HoveredState | null>(null)

  const stateDataByCode = useMemo(() => {
    return new Map(data.map((item) => [item.stateCode, item]))
  }, [data])

  const maxCustomers = useMemo(() => {
    return Math.max(...data.map((item) => item.customerCount), 1)
  }, [data])

  const geographies = useMemo(() => {
    const topology = statesTopoJson as unknown as {
      objects: {
        states: object
      }
    }

    const collection = feature(
      topology as never,
      topology.objects.states as never,
    ) as unknown as {
      features: GeoFeature[]
    }

    return collection.features
  }, [])

  const pathGenerator = useMemo(() => {
    const projection = geoAlbersUsa()
      .translate([MAP_WIDTH / 2, MAP_HEIGHT / 2])
      .scale(1030)

    return geoPath(projection)
  }, [])

  const totalCustomers = data.reduce(
    (sum, item) => sum + item.customerCount,
    0,
  )

  const totalOrders = data.reduce(
    (sum, item) => sum + item.orderCount,
    0,
  )

  const totalRevenue = data.reduce(
    (sum, item) => sum + Number(item.revenue),
    0,
  )

  const topMarkets = [...data]
    .sort((a, b) => b.customerCount - a.customerCount)
    .slice(0, 5)

  const topMarketCustomers = topMarkets.reduce(
    (sum, item) => sum + item.customerCount,
    0,
  )

  const topMarketShare =
    totalCustomers > 0 ? (topMarketCustomers / totalCustomers) * 100 : 0

  const strokeColor = isDark ? 'rgba(148, 163, 184, 0.32)' : '#cbd5e1'
  const activeStrokeColor = isDark ? 'rgba(191, 219, 254, 0.48)' : '#93c5fd'

  const wrapperClass = 'grid items-stretch gap-5 xl:grid-cols-[minmax(0,1fr)_340px]'

  const mapPanelClass = [
    'relative h-full min-h-[520px] overflow-hidden rounded-2xl border p-4',
    isDark
      ? 'border-slate-800 bg-slate-950/50 shadow-inner shadow-black/20'
      : 'border-slate-200 bg-slate-50',
  ].join(' ')

  const mapInnerClass = [
    'flex h-full min-h-[488px] items-center justify-center rounded-xl',
    isDark
      ? 'bg-[radial-gradient(circle_at_center,rgba(79,70,229,0.16),rgba(15,23,42,0.22)_48%,rgba(2,6,23,0.18)_100%)]'
      : 'bg-[radial-gradient(circle_at_center,rgba(219,234,254,0.65),rgba(248,250,252,0.9)_58%,rgba(248,250,252,1)_100%)]',
  ].join(' ')

  return (
    <div className={wrapperClass}>
      <div className={mapPanelClass}>
        <div className={mapInnerClass}>
          <svg
            viewBox={`0 0 ${MAP_WIDTH} ${MAP_HEIGHT}`}
            role="img"
            aria-label="United States customer distribution map"
            className="h-full max-h-[470px] w-full"
          >
            {geographies.map((geo) => {
              const stateCode = getStateCodeFromGeoId(geo.id)
              const stateData = stateCode ? stateDataByCode.get(stateCode) : null
              const customerCount = stateData?.customerCount ?? 0
              const isHovered =
                Boolean(stateCode) && hoveredState?.stateCode === stateCode

              const fill = getStateFill({
                customerCount,
                maxCustomers,
                isDark,
                isHovered,
              })

              const path = pathGenerator(geo as never) ?? ''

              return (
                <path
                  key={String(geo.id)}
                  d={path}
                  fill={fill}
                  stroke={customerCount > 0 ? activeStrokeColor : strokeColor}
                  strokeWidth={isHovered ? 1.4 : customerCount > 0 ? 0.8 : 0.55}
                  className="transition-colors duration-150"
                  style={{
                    cursor: 'pointer',
                    filter: isHovered
                      ? isDark
                        ? 'drop-shadow(0 0 8px rgba(167, 139, 250, 0.35))'
                        : 'drop-shadow(0 0 5px rgba(37, 99, 235, 0.25))'
                      : undefined,
                  }}
                  onMouseMove={(event) => {
                    const fallbackName =
                      typeof geo.properties?.name === 'string'
                        ? geo.properties.name
                        : stateCode ?? 'Unknown'

                    setHoveredState({
                      stateName: stateData?.state ?? fallbackName,
                      stateCode: stateCode ?? '',
                      customerCount,
                      orderCount: stateData?.orderCount ?? 0,
                      revenue: stateData?.revenue ?? 0,
                      x: event.clientX,
                      y: event.clientY,
                    })
                  }}
                  onMouseLeave={() => {
                    setHoveredState(null)
                  }}
                />
              )
            })}
          </svg>
        </div>

        <div
          className={[
            'absolute bottom-5 left-5 rounded-2xl border px-3 py-2 text-xs shadow-sm',
            isDark
              ? 'border-slate-800 bg-slate-950/80 text-slate-400 shadow-black/20'
              : 'border-slate-200 bg-white/90 text-slate-500 shadow-slate-200/70',
          ].join(' ')}
        >
          <div className="mb-2 font-semibold uppercase tracking-[0.12em]">
            Customer density
          </div>

          <div className="flex items-center gap-2">
            <span>Low</span>
            <div
              className={[
                'h-2 w-28 rounded-full',
                isDark
                  ? 'bg-gradient-to-r from-slate-800 via-indigo-500/50 to-violet-400'
                  : 'bg-gradient-to-r from-slate-100 via-blue-300 to-blue-700',
              ].join(' ')}
            />
            <span>High</span>
          </div>
        </div>

        {hoveredState ? (
          <div
            className={[
              'pointer-events-none fixed z-[70] min-w-[220px] rounded-2xl border p-3 shadow-xl',
              isDark
                ? 'border-slate-700 bg-slate-950 text-slate-100 shadow-black/40'
                : 'border-slate-200 bg-white text-slate-950 shadow-slate-300/40',
            ].join(' ')}
            style={{
              left: hoveredState.x + 14,
              top: hoveredState.y + 14,
            }}
          >
            <div className="text-sm font-semibold">
              {hoveredState.stateName}
              {hoveredState.stateCode ? ` (${hoveredState.stateCode})` : ''}
            </div>

            <div
              className={[
                'mt-2 space-y-1 text-xs',
                isDark ? 'text-slate-400' : 'text-slate-500',
              ].join(' ')}
            >
              <div>Customers: {formatNumber(hoveredState.customerCount)}</div>
              <div>Orders: {formatNumber(hoveredState.orderCount)}</div>
              <div>Revenue: {formatCurrency(hoveredState.revenue)}</div>
            </div>
          </div>
        ) : null}
      </div>

      <div
        className={[
          'flex h-full flex-col rounded-2xl border p-4',
          isDark
            ? 'border-slate-800 bg-slate-950/50 shadow-inner shadow-black/20'
            : 'border-slate-200 bg-white',
        ].join(' ')}
      >
        <div className="flex items-start justify-between gap-4">
          <div>
            <div
              className={[
                'text-xs font-semibold uppercase tracking-[0.12em]',
                isDark ? 'text-slate-500' : 'text-slate-400',
              ].join(' ')}
            >
              Top Markets
            </div>

            <div
              className={[
                'mt-1 text-sm',
                isDark ? 'text-slate-400' : 'text-slate-500',
              ].join(' ')}
            >
              Ranked by customer count
            </div>
          </div>

          <div className="text-right">
            <div
              className={[
                'font-data text-sm font-semibold',
                isDark ? 'text-white' : 'text-slate-950',
              ].join(' ')}
            >
              {topMarketShare.toFixed(1)}%
            </div>

            <div
              className={[
                'text-xs',
                isDark ? 'text-slate-500' : 'text-slate-400',
              ].join(' ')}
            >
              top 5 share
            </div>
          </div>
        </div>

        <div className="mt-5 flex-1 space-y-4">
          {topMarkets.map((item, index) => {
            const share =
              totalCustomers > 0
                ? (item.customerCount / totalCustomers) * 100
                : 0

            const avgRevenuePerCustomer =
              item.customerCount > 0
                ? Number(item.revenue) / item.customerCount
                : 0

            return (
              <MarketRow
                key={item.stateCode}
                rank={index + 1}
                state={item.state}
                customers={item.customerCount}
                orders={item.orderCount}
                share={share}
                avgRevenuePerCustomer={avgRevenuePerCustomer}
                maxCustomers={maxCustomers}
                variant={variant}
              />
            )
          })}

          {!topMarkets.length ? (
            <div
              className={[
                'py-8 text-center text-sm',
                isDark ? 'text-slate-400' : 'text-slate-500',
              ].join(' ')}
            >
              No market data available.
            </div>
          ) : null}
        </div>

        <div
          className={[
            'mt-5 rounded-2xl border px-3 py-3',
            isDark
              ? 'border-slate-800 bg-slate-900/80'
              : 'border-slate-200 bg-slate-50',
          ].join(' ')}
        >
          <div className="grid grid-cols-[0.65fr_0.75fr_1.8fr] gap-2 text-center">
            <MiniMetric
              label="States"
              value={formatNumber(data.length)}
              variant={variant}
            />
            <MiniMetric
              label="Orders"
              value={formatNumber(totalOrders)}
              variant={variant}
            />
            <MiniMetric
              label="Revenue"
              value={formatCurrency(totalRevenue)}
              variant={variant}
            />
          </div>
        </div>
      </div>
    </div>
  )
}

function MarketRow({
  rank,
  state,
  customers,
  orders,
  share,
  avgRevenuePerCustomer,
  maxCustomers,
  variant,
}: {
  rank: number
  state: string
  customers: number
  orders: number
  share: number
  avgRevenuePerCustomer: number
  maxCustomers: number
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'
  const width = maxCustomers > 0 ? Math.max(8, (customers / maxCustomers) * 100) : 0

  return (
    <div>
      <div className="flex items-start justify-between gap-3">
        <div className="min-w-0">
          <div
            className={[
              'truncate text-sm font-semibold',
              isDark ? 'text-white' : 'text-slate-950',
            ].join(' ')}
          >
            {rank}. {state}
          </div>

          <div
            className={[
              'mt-0.5 text-xs',
              isDark ? 'text-slate-400' : 'text-slate-500',
            ].join(' ')}
          >
            {formatNumber(customers)} customers / {formatNumber(orders)} orders
          </div>
        </div>

        <div className="shrink-0 text-right">
          <div
            className={[
              'font-data text-sm font-semibold',
              isDark ? 'text-white' : 'text-slate-950',
            ].join(' ')}
          >
            {share.toFixed(1)}%
          </div>

          <div
            className={[
              'text-xs',
              isDark ? 'text-slate-500' : 'text-slate-400',
            ].join(' ')}
          >
            share
          </div>
        </div>
      </div>

      <div
        className={[
          'mt-2 h-2 overflow-hidden rounded-full',
          isDark ? 'bg-slate-800' : 'bg-slate-100',
        ].join(' ')}
      >
        <div
          className={[
            'h-full rounded-full',
            isDark ? 'bg-violet-500' : 'bg-blue-600',
          ].join(' ')}
          style={{
            width: `${width}%`,
          }}
        />
      </div>

      <div
        className={[
          'mt-1 text-xs',
          isDark ? 'text-slate-500' : 'text-slate-400',
        ].join(' ')}
      >
        {formatCurrency(avgRevenuePerCustomer)} revenue/customer
      </div>
    </div>
  )
}

function MiniMetric({
  label,
  value,
  variant,
}: {
  label: string
  value: string
  variant: 'light' | 'dark'
}) {
  const isDark = variant === 'dark'

  return (
    <div className="min-w-0">
      <div
        className={[
          'whitespace-nowrap font-data text-[11px] font-semibold leading-tight',
          isDark ? 'text-white' : 'text-slate-950',
        ].join(' ')}
      >
        {value}
      </div>

      <div
        className={[
          'mt-1 text-[10px] font-semibold uppercase tracking-[0.12em]',
          isDark ? 'text-slate-500' : 'text-slate-400',
        ].join(' ')}
      >
        {label}
      </div>
    </div>
  )
}