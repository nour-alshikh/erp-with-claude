'use client';

import { useAuth } from './useAuth';

export function usePermissions() {
  const { user } = useAuth();

  function hasPermission(permission: string): boolean {
    if (!user) return false;
    if (user.roles.includes('Super Admin')) return true;
    return user.permissions.includes(permission);
  }

  function hasAnyPermission(permissions: string[]): boolean {
    return permissions.some(hasPermission);
  }

  function hasAllPermissions(permissions: string[]): boolean {
    return permissions.every(hasPermission);
  }

  return { hasPermission, hasAnyPermission, hasAllPermissions };
}
