import client from './client';
import type { AuthUser, LoginCredentials } from '../types/auth';

export const authApi = {
  login: (credentials: LoginCredentials) =>
    client.post<{ data: { token: string; user: AuthUser } }>('/auth/login', credentials),

  logout: () => client.post('/auth/logout'),

  me: () => client.get<{ data: AuthUser }>('/auth/me'),
};
