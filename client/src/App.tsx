import { Navigate, Route, Routes } from 'react-router-dom'
import DashboardLayout from './layouts/DashboardLayout'
import OverviewPage from './pages/dashboard/OverviewPage'
import OrdersPage from './pages/orders/OrdersPage'

export default function App() {
  return (
    <Routes>
      <Route path="/" element={<Navigate to="/dashboard" replace />} />

      <Route element={<DashboardLayout />}>
        <Route path="/dashboard" element={<OverviewPage />} />
        <Route path="/orders" element={<OrdersPage />} />
      </Route>
    </Routes>
  )
}