// import { useMemo, useState } from 'react'
// import { scaleQuantize } from 'd3-scale'
// import {
//     ComposableMap,
//     Geographies,
//     Geography,
// } from '@vnedyalk0v/react19-simple-maps'
// import geoData from 'us-atlas/states-10m.json'

// type StateCount = {
//     state: string
//     count: number
// }

// type StateMapProps = {
//     customers: StateCount[]
//     businesses: StateCount[]
//     metric: 'customers' | 'businesses'
// }

// type ActiveState = {
//     state: string
//     count: number
// }

// export default function StateMap({
//     customers,
//     businesses,
//     metric,
// }: StateMapProps) {
//     const [hovered, setHovered] = useState<ActiveState | null>(null)

//     const activeData = metric === 'customers' ? customers : businesses

//     const dataMap = useMemo(() => {
//         return activeData.reduce<Record<string, number>>((acc, item) => {
//             acc[item.state] = item.count
//             return acc
//         }, {})
//     }, [activeData])

//     const total = useMemo(() => {
//         return activeData.reduce((sum, item) => sum + item.count, 0)
//     }, [activeData])

//     const maxCount = useMemo(() => {
//         return Math.max(...activeData.map((item) => item.count), 0)
//     }, [activeData])

//     const colorRange = [
//         '#EEF2FF',
//         '#DDD6FE',
//         '#C4B5FD',
//         '#A78BFA',
//         '#8B5CF6',
//         '#7C3AED',
//     ]

//     const colorScale = useMemo(() => {
//         return scaleQuantize<string>()
//             .domain([0, Math.max(maxCount, 1)])
//             .range(colorRange)
//     }, [maxCount])

//     const activeLabel =
//         hovered ?? {
//             state: 'Hover a state',
//             count: 0,
//         }

//     return (
//         <div className="space-y-4">
//             <div className="flex flex-wrap items-center justify-between gap-3">
//                 <span className="inline-flex items-center rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-600">
//                     Total {metric === 'customers' ? 'Customers' : 'Businesses'}:&nbsp;
//                     <span className="font-semibold text-slate-900">{total}</span>
//                 </span>

//                 <div className="inline-flex items-center rounded-full bg-slate-100 px-4 py-2 text-sm text-slate-600">
//                     <span className="font-medium text-slate-900">{activeLabel.state}</span>
//                     {activeLabel.state !== 'Hover a state' ? (
//                         <span className="ml-2">
//                             {activeLabel.count} {metric === 'customers' ? 'customers' : 'businesses'}
//                         </span>
//                     ) : null}
//                 </div>
//             </div>

//             <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
//                 <div className="mx-auto w-full max-w-[900px]">
//                     <ComposableMap
//                         projection="geoAlbersUsa"
//                         width={980}
//                         height={600}
//                         style={{ width: '100%', height: 'auto' }}
//                     >
//                         <Geographies geography={geoData}>
//                             {({ geographies }) =>
//                                 geographies.map((geo) => {
//                                     const stateName = geo.properties?.name as string
//                                     const count = dataMap[stateName] ?? 0

//                                     return (
//                                         <Geography
//                                             key={`${stateName}`}
//                                             geography={geo}
//                                             tabIndex={-1}
//                                             onMouseEnter={() => setHovered({ state: stateName, count })}
//                                             onMouseLeave={() => setHovered(null)}
//                                             onMouseDown={(e) => e.preventDefault()}
//                                             style={{
//                                                 default: {
//                                                     fill: count > 0 ? colorScale(count) : '#E5E7EB',
//                                                     stroke: '#FFFFFF',
//                                                     strokeWidth: 1,
//                                                     outline: 'none',
//                                                 },
//                                                 hover: {
//                                                     fill: count > 0 ? '#6D28D9' : '#CBD5E1',
//                                                     stroke: '#FFFFFF',
//                                                     strokeWidth: 1.5,
//                                                     outline: 'none',
//                                                 },
//                                                 pressed: {
//                                                     fill: count > 0 ? '#6D28D9' : '#CBD5E1',
//                                                     stroke: '#FFFFFF',
//                                                     strokeWidth: 1.5,
//                                                     outline: 'none',
//                                                 },
//                                             }}
//                                         />
//                                     )
//                                 })
//                             }
//                         </Geographies>
//                     </ComposableMap>
//                 </div>
//             </div>

//             <div className="flex flex-wrap items-center gap-3 text-xs text-slate-500">
//                 <span className="font-medium text-slate-600">Less</span>
//                 {colorRange.map((color) => (
//                     <span
//                         key={color}
//                         className="h-3 w-7 rounded"
//                         style={{ backgroundColor: color }}
//                     />
//                 ))}
//                 <span className="font-medium text-slate-600">More</span>
//             </div>
//         </div>
//     )
// }