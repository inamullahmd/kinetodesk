import { useEffect, useMemo, useRef, useState } from 'react'
import ReactApexChart from 'react-apexcharts'
import type { ApexOptions } from 'apexcharts'

type BarChartProps = {
  categories: string[]
  data: number[]
  seriesName?: string
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
}: BarChartProps) {
  const outerRef = useRef<HTMLDivElement | null>(null)
  const [containerWidth, setContainerWidth] = useState(0)

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

  const formattedCategories = useMemo(
    () => categories.map(shortenLabel),
    [categories]
  )

  const enableHorizontalScroll = containerWidth > 0 && containerWidth < 640
  const chartWidth = enableHorizontalScroll
    ? Math.max(formattedCategories.length * 72, 720)
    : containerWidth || 800

  const shouldRotateLabels = useMemo(() => {
    if (!formattedCategories.length || !chartWidth) return false
    const widthPerLabel = chartWidth / formattedCategories.length
    return widthPerLabel < 64
  }, [formattedCategories, chartWidth])

  const options: ApexOptions = {
    chart: {
      type: 'bar',
      toolbar: { show: false },
      parentHeightOffset: 0,
    },
    colors: ['#7C3AED'],
    plotOptions: {
      bar: {
        horizontal: false,
        columnWidth: '40%',
        borderRadius: 6,
        borderRadiusApplication: 'end',
      } as any,
    },
    dataLabels: {
      enabled: false,
    },
    grid: {
      borderColor: '#E2E8F0',
      padding: {
        top: 8,
        right: 8,
        left: 8,
        bottom: shouldRotateLabels ? 26 : 12,
      },
    },
    xaxis: {
      categories: formattedCategories,
      axisBorder: {
        show: true,
        color: '#CBD5E1',
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
        minHeight: shouldRotateLabels ? 56 : 34,
        maxHeight: shouldRotateLabels ? 72 : 40,
        style: {
          fontSize: '12px',
        },
      },
    },
    yaxis: {
      labels: {
        formatter: (value) => `$${Math.round(value).toLocaleString()}`,
      },
    },
    legend: {
      show: false,
    },
    tooltip: {
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
            ? 'w-full max-w-full overflow-x-auto overflow-y-hidden pb-2'
            : 'w-full max-w-full'
        }
        style={enableHorizontalScroll ? { WebkitOverflowScrolling: 'touch' } : undefined}
      >
        <div
          className="h-[380px]"
          style={{
            width: enableHorizontalScroll ? `${chartWidth}px` : '100%',
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