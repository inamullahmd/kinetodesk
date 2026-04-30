import { Navigate, Route, Routes } from 'react-router-dom'
import DashboardLayout from './layouts/DashboardLayout'
import OverviewPage from './pages/dashboard/OverviewPage'
import OrdersPage from './pages/orders/OrdersPage'
import InventoryOverviewPage from './pages/inventory/InventoryOverviewPage'
import InventoryProductsPage from './pages/inventory/InventoryProductsPage'
import InventoryAlertsPage from './pages/inventory/InventoryAlertsPage'

export default function App() {
  return (
    <Routes>
      <Route path="/" element={<Navigate to="/dashboard" replace />} />

      <Route element={<DashboardLayout />}>
        <Route path="/dashboard" element={<OverviewPage />} />
        <Route path="/orders" element={<OrdersPage />} />

<Route path="/inventory" element={<InventoryOverviewPage />} />
<Route path="/inventory/products" element={<InventoryProductsPage />} />
<Route path="/inventory/alerts" element={<InventoryAlertsPage />} />
      </Route>
    </Routes>
  )
}