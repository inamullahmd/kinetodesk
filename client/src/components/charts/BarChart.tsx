import { useEffect, useMemo, useRef, useState } from 'react'
import ReactApexChart from 'react-apexcharts'
import type { ApexOptions } from 'apexcharts'

type BarChartProps = {
  categories: string[]
  data: number[]
  seriesName?: string
  variant?: 'light' | 'dark'
  height?: number
}

function shortenLabel(label: string) {
  if (label.startsWith('Q')) return label

  const parts = label.split(' ')
  if (parts.length >= 2) {
    const month = parts[0].slice(0, 3)
    const year = parts[1].slice(-2)
    return `${month} '${year}`
  }

  return label
}

export default function BarChart({
  categories,
  data,
  seriesName = 'Revenue',
  variant = 'light',
  height = 360,
}: BarChartProps) {
  const isDark = variant === 'dark'
  const outerRef = useRef<HTMLDivElement | null>(null)
  const [containerWidth, setContainerWidth] = useState(0)
  const [windowWidth, setWindowWidth] = useState<number>(
    typeof window !== 'undefined' ? window.innerWidth : 1440
  )

  useEffect(() => {
    if (!outerRef.current) return

    const element = outerRef.current
    const observer = new ResizeObserver((entries) => {
      const entry = entries[0]
      if (!entry) return
      setContainerWidth(entry.contentRect.width)
    })

    observer.observe(element)
    return () => observer.disconnect()
  }, [])

  useEffect(() => {
    const onResize = () => setWindowWidth(window.innerWidth)
    window.addEventListener('resize', onResize)
    return () => window.removeEventListener('resize', onResize)
  }, [])

  const formattedCategories = useMemo(() => categories.map(shortenLabel), [categories])

  const enableHorizontalScroll =
    containerWidth > 0 && containerWidth < 760 && windowWidth < 1024

  const chartWidth = enableHorizontalScroll
    ? Math.max(formattedCategories.length * 72, 780)
    : containerWidth || 860

  const shouldRotateLabels = useMemo(() => {
    if (!formattedCategories.length || !chartWidth) return false
    const widthPerLabel = chartWidth / formattedCategories.length
    return widthPerLabel < 64
  }, [formattedCategories, chartWidth])

  const axisColor = isDark ? '#CBD5E1' : '#475569'
  const gridColor = isDark ? 'rgba(148,163,184,0.18)' : '#E2E8F0'
  const axisBorderColor = isDark ? 'rgba(148,163,184,0.25)' : '#CBD5E1'

  const options: ApexOptions = {
    chart: {
      type: 'bar',
      toolbar: { show: false },
      parentHeightOffset: 0,
      background: 'transparent',
    },
    theme: {
      mode: isDark ? 'dark' : 'light',
    },
    colors: ['#6822FF'],
    fill: {
      type: 'solid',
      opacity: 1,
    },
    plotOptions: {
      bar: {
        horizontal: false,
        columnWidth: '48%',
        borderRadius: 6,
        borderRadiusApplication: 'end',
      } as any,
    },
    dataLabels: {
      enabled: false,
    },
    stroke: {
      show: false,
    },
    grid: {
      borderColor: gridColor,
      padding: {
        top: 8,
        right: 12,
        left: 8,
        bottom: shouldRotateLabels ? 28 : 14,
      },
    },
    xaxis: {
      categories: formattedCategories,
      axisBorder: {
        show: true,
        color: axisBorderColor,
      },
      axisTicks: {
        show: false,
      },
      labels: {
        rotate: shouldRotateLabels ? -45 : 0,
        rotateAlways: shouldRotateLabels,
        hideOverlappingLabels: false,
        trim: false,
        offsetY: 8,
        minHeight: shouldRotateLabels ? 56 : 36,
        maxHeight: shouldRotateLabels ? 72 : 42,
        style: {
          fontSize: '12px',
          colors: formattedCategories.map(() => axisColor),
          fontWeight: 500,
        },
      },
    },
    yaxis: {
      labels: {
        formatter: (value) => `$${Math.round(value).toLocaleString()}`,
        style: {
          colors: [axisColor],
          fontSize: '12px',
          fontWeight: 500,
        },
      },
    },
    legend: {
      show: false,
    },
    tooltip: {
      theme: isDark ? 'dark' : 'light',
      y: {
        formatter: (value) => `$${value.toLocaleString()}`,
      },
    },
  }

  const series = [
    {
      name: seriesName,
      data,
    },
  ]

  return (
    <div ref={outerRef} className="w-full max-w-full">
      <div
        className={
          enableHorizontalScroll
            ? 'w-full max-w-full overflow-x-auto overflow-y-hidden pb-2 lg:overflow-visible'
            : 'w-full max-w-full overflow-hidden'
        }
        style={enableHorizontalScroll ? { WebkitOverflowScrolling: 'touch' } : undefined}
      >
        <div
          style={{
            width: enableHorizontalScroll ? `${chartWidth}px` : '100%',
            height: `${height}px`,
          }}
        >
          <ReactApexChart
            type="bar"
            height="100%"
            width={enableHorizontalScroll ? chartWidth : '100%'}
            options={options}
            series={series}
          />
        </div>
      </div>
    </div>
  )
}