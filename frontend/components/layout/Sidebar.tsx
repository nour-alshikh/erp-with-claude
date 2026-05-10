'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useAuth } from '@/lib/hooks/useAuth';
import { usePermissions } from '@/lib/hooks/usePermissions';
import { useTheme } from '@/providers/ThemeProvider';
import { useDirection } from '@/providers/DirectionProvider';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Moon, Sun, X, Languages } from 'lucide-react';

const navItems = [
  { label: 'Dashboard',  href: '/dashboard',                    permission: null },
  { label: 'HR',         href: '/hr/employees',                 permission: 'view-hr' },
  { label: 'Accounting', href: '/accounting/chart-of-accounts', permission: 'view-accounting' },
  { label: 'Inventory',  href: '/inventory/products',           permission: 'view-inventory' },
  { label: 'Sales',      href: '/sales/customers',              permission: 'view-sales' },
  { label: 'Purchasing', href: '/purchasing/vendors',           permission: 'view-purchasing' },
  { label: 'POS',        href: '/pos',                          permission: 'use-pos' },
  { label: 'Reports',    href: '/reports',                      permission: 'view-reports' },
];

export function Sidebar({ onClose }: { onClose?: () => void }) {
  const pathname = usePathname();
  const { user, logout } = useAuth();
  const { hasPermission } = usePermissions();
  const { theme, toggle: toggleTheme } = useTheme();
  const { dir, toggle: toggleDir } = useDirection();

  const visible = navItems.filter(
    (item) => item.permission === null || hasPermission(item.permission)
  );

  return (
    <aside className="w-60 h-full bg-background border-e flex flex-col">
      <div className="p-4 border-b flex items-center justify-between gap-2">
        <div className="min-w-0">
          <h2 className="font-bold text-lg">ERP System</h2>
          <p className="text-xs text-muted-foreground truncate">{user?.name}</p>
        </div>
        {onClose && (
          <button
            onClick={onClose}
            className="p-1 rounded hover:bg-muted shrink-0"
            aria-label="Close menu"
          >
            <X className="h-4 w-4" />
          </button>
        )}
      </div>

      <nav className="flex-1 p-2 space-y-1 overflow-y-auto">
        {visible.map((item) => (
          <Link key={item.href} href={item.href} onClick={onClose}>
            <span
              className={cn(
                'block px-3 py-2 rounded-md text-sm font-medium transition-colors',
                pathname === item.href || pathname.startsWith(item.href + '/')
                  ? 'bg-primary text-primary-foreground'
                  : 'text-muted-foreground hover:bg-muted hover:text-foreground'
              )}
            >
              {item.label}
            </span>
          </Link>
        ))}
      </nav>

      <div className="p-3 border-t space-y-1">
        <div className="flex gap-1">
          <Button
            variant="ghost"
            size="sm"
            className="flex-1 justify-start gap-2"
            onClick={toggleTheme}
          >
            {theme === 'dark' ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
            {theme === 'dark' ? 'Light' : 'Dark'}
          </Button>
          <Button
            variant="ghost"
            size="sm"
            className="flex-1 justify-start gap-2"
            onClick={toggleDir}
          >
            <Languages className="h-4 w-4" />
            {dir === 'ltr' ? 'AR' : 'EN'}
          </Button>
        </div>
        <Button variant="ghost" size="sm" className="w-full justify-start" onClick={logout}>
          Sign Out
        </Button>
      </div>
    </aside>
  );
}
