import { useEffect, useMemo, useRef, useState } from 'react'
import ReactApexChart from 'react-apexcharts'
import type { ApexOptions } from 'apexcharts'

type OrdersTrendChartProps = {
  categories: string[]
  data: number[]
}

export default function OrdersTrendChart({
  categories,
  data,
}: OrdersTrendChartProps) {
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

  const enableHorizontalScroll = containerWidth > 0 && containerWidth < 640
  const chartWidth = enableHorizontalScroll
    ? Math.max(categories.length * 52, 720)
    : containerWidth || 800

  const shouldRotateLabels = useMemo(() => {
    if (!categories.length || !chartWidth) return false
    const widthPerLabel = chartWidth / categories.length
    return widthPerLabel < 44
  }, [categories, chartWidth])

  const options: ApexOptions = {
    chart: {
      type: 'line',
      toolbar: { show: false },
      zoom: { enabled: false },
      parentHeightOffset: 0,
    },
    colors: ['#7C3AED'],
    stroke: {
      curve: 'smooth',
      width: 3,
    },
    markers: {
      size: 4,
      hover: {
        size: 6,
      },
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
        bottom: shouldRotateLabels ? 28 : 12,
      },
    },
    xaxis: {
      categories,
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
        formatter: (value) => Math.round(value).toLocaleString(),
      },
    },
    tooltip: {
      y: {
        formatter: (value) => `${Math.round(value).toLocaleString()} orders`,
      },
    },
    legend: {
      show: false,
    },
  }

  const series = [
    {
      name: 'Orders',
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
          className="h-[360px]"
          style={{
            width: enableHorizontalScroll ? `${chartWidth}px` : '100%',
          }}
        >
          <ReactApexChart
            type="line"
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