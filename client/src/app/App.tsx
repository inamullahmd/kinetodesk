import { Navigate, Route, Routes } from 'react-router-dom'
import DashboardLayout from './DashboardLayout'
import OverviewPage from '../modules/dashboard/OverviewPage'
import OrdersPage from '../modules/orders/OrdersPage'
import InventoryOverviewPage from '../modules/inventory/InventoryOverviewPage'
import InventoryProductsPage from '../modules/inventory/InventoryProductsPage'
import InventoryAlertsPage from '../modules/inventory/InventoryAlertsPage'
import CustomersPage from '../modules/directory/CustomersPage'
import SuppliersPage from '../modules/directory/SuppliersPage'
import EmployeesPage from '../modules/directory/EmployeesPage'

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

        <Route path="/directory/customers" element={<CustomersPage />} />
        <Route path="/directory/suppliers" element={<SuppliersPage />} />
        <Route path="/directory/employees" element={<EmployeesPage />} />
      </Route>
    </Routes>
  )
}