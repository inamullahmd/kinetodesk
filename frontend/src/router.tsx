import { createBrowserRouter, Navigate } from "react-router-dom";
import type { ReactElement } from "react";
import { LoginPage } from "./screens/LoginPage";
import { DashboardPage } from "./screens/DashboardPage";
import { ProductsPage } from "./screens/ProductsPage";
import { PurchaseOrdersPage } from "./screens/PurchaseOrdersPage";
import { OrdersPage } from "./screens/OrdersPage";
import { AppLayout } from "./ui/AppLayout";
import { useAuthStore } from "./store/auth";

function ProtectedRoute({ children }: { children: ReactElement }) {
  const token = useAuthStore.getState().token;
  return token ? children : <Navigate to="/login" replace />;
}

function GuestRoute({ children }: { children: ReactElement }) {
  const token = useAuthStore.getState().token;
  return token ? <Navigate to="/" replace /> : children;
}

export const router = createBrowserRouter([
  {
    path: "/login",
    element: (
      <GuestRoute>
        <LoginPage />
      </GuestRoute>
    ),
  },
  {
    path: "/",
    element: (
      <ProtectedRoute>
        <AppLayout />
      </ProtectedRoute>
    ),
    children: [
      { index: true, element: <DashboardPage /> },
      { path: "products", element: <ProductsPage /> },
      { path: "purchase-orders", element: <PurchaseOrdersPage /> },
      { path: "orders", element: <OrdersPage /> },
    ],
  },
]);