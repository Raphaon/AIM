export interface ApiResponse<T> {
  status: string;
  message?: string;
  data: T;
}

export interface PaginatedCollection<T> {
  current_page: number;
  data: T[];
  last_page: number;
  per_page: number;
  total: number;
}

export interface Permission {
  id: number;
  name: string;
  slug: string;
  description?: string;
  created_at?: string;
  updated_at?: string;
}

export interface Role {
  id: number;
  name: string;
  description?: string;
  permissions?: Permission[];
  created_at?: string;
  updated_at?: string;
}

export interface User {
  id: number;
  name: string;
  email: string;
  phone?: string | null;
  status?: 'active' | 'suspended';
  roles?: Role[];
  permissions?: Permission[];
  last_login_at?: string | null;
  login_count?: number;
  created_at?: string;
  updated_at?: string;
}

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface AuthResponse {
  token: string | null;
  user: User;
}

export interface RolePayload {
  name: string;
  description?: string;
  permissions?: string[];
}

export interface PermissionPayload {
  name: string;
  slug: string;
  description?: string;
}

export interface UserPayload {
  name: string;
  email: string;
  password?: string;
  phone?: string | null;
  status?: 'active' | 'suspended';
  roles?: string[];
  permissions?: string[];
}
