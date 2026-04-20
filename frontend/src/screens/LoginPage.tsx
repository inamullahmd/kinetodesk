import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { login, me } from "../api/auth";
import { useAuthStore } from "../store/auth";

export function LoginPage() {
  const navigate = useNavigate();
  const setAuth = useAuthStore((s) => s.setAuth);
  const clearAuth = useAuthStore((s) => s.clearAuth);

  const [email, setEmail] = useState("admin@kinetodesk.local");
  const [password, setPassword] = useState("Admin@12345");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setLoading(true);
    setError("");

    try {
      const loginResponse = await login({ email, password });
      const token = loginResponse.token;

      if (!token) {
        throw new Error("Login succeeded but no token was returned.");
      }

      // Save token first so the /me request includes Authorization header
      setAuth(token, {
        id: 0,
        name: "Loading...",
        email,
      });

      const meResponse = await me();

      setAuth(token, {
        id: meResponse.id,
        name: meResponse.name,
        email: meResponse.email,
      });

      navigate("/");
    } catch (err: any) {
      clearAuth();
      setError(err?.response?.data?.message || err?.message || "Login failed");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center p-6">
      <form
        onSubmit={handleSubmit}
        className="w-full max-w-md rounded-2xl border bg-white p-8 shadow-sm"
      >
        <h1 className="text-2xl font-semibold">KinetoDesk Login</h1>
        <p className="mt-2 text-sm text-slate-500">
          Sign in to access the admin dashboard.
        </p>

        <div className="mt-6 space-y-4">
          <div>
            <label className="mb-1 block text-sm font-medium">Email</label>
            <input
              className="w-full rounded-lg border px-3 py-2"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              type="email"
            />
          </div>

          <div>
            <label className="mb-1 block text-sm font-medium">Password</label>
            <input
              className="w-full rounded-lg border px-3 py-2"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              type="password"
            />
          </div>
        </div>

        {error ? <p className="mt-4 text-sm text-red-600">{error}</p> : null}

        <button
          disabled={loading}
          className="mt-6 w-full rounded-lg bg-slate-900 px-4 py-2 text-white disabled:opacity-60"
        >
          {loading ? "Signing in..." : "Login"}
        </button>
      </form>
    </div>
  );
}