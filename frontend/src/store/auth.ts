import { create } from "zustand";

type User = {
  id: number;
  name: string;
  email: string;
} | null;

type AuthState = {
  token: string | null;
  user: User;
  setAuth: (token: string, user: NonNullable<User>) => void;
  clearAuth: () => void;
};

const storedToken = localStorage.getItem("auth_token");
const storedUser = localStorage.getItem("auth_user");

export const useAuthStore = create<AuthState>((set) => ({
  token: storedToken,
  user: storedUser ? JSON.parse(storedUser) : null,
  setAuth: (token, user) => {
    localStorage.setItem("auth_token", token);
    localStorage.setItem("auth_user", JSON.stringify(user));
    set({ token, user });
  },
  clearAuth: () => {
    localStorage.removeItem("auth_token");
    localStorage.removeItem("auth_user");
    set({ token: null, user: null });
  },
}));