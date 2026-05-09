'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useAuth } from '@/lib/hooks/useAuth';
import { usePermissions } from '@/lib/hooks/usePermissions';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';

const navItems = [
  { label: 'Dashboard',   href: '/',                    permission: null },
  { label: 'HR',          href: '/hr/employees',         permission: 'view-hr' },
  { label: 'Accounting',  href: '/accounting/chart-of-accounts', permission: 'view-accounting' },
  { label: 'Inventory',   href: '/inventory/products',   permission: 'view-inventory' },
  { label: 'Sales',       href: '/sales/customers',      permission: 'view-sales' },
  { label: 'Purchasing',  href: '/purchasing/vendors',   permission: 'view-purchasing' },
  { label: 'POS',         href: '/pos',                  permission: 'use-pos' },
  { label: 'Reports',     href: '/reports',              permission: 'view-reports' },
];

export function Sidebar() {
  const pathname = usePathname();
  const { user, logout } = useAuth();
  const { hasPermission } = usePermissions();

  const visible = navItems.filter(
    (item) => item.permission === null || hasPermission(item.permission)
  );

  return (
    <aside className="w-60 h-full bg-white border-r flex flex-col">
      <div className="p-4 border-b">
        <h2 className="font-bold text-lg">ERP System</h2>
        <p className="text-xs text-muted-foreground truncate">{user?.name}</p>
      </div>

      <nav className="flex-1 p-2 space-y-1 overflow-y-auto">
        {visible.map((item) => (
          <Link key={item.href} href={item.href}>
            <span
              className={cn(
                'block px-3 py-2 rounded-md text-sm font-medium transition-colors',
                pathname === item.href || (item.href !== '/' && pathname.startsWith(item.href))
                  ? 'bg-primary text-primary-foreground'
                  : 'text-gray-700 hover:bg-gray-100'
              )}
            >
              {item.label}
            </span>
          </Link>
        ))}
      </nav>

      <div className="p-4 border-t">
        <Button variant="ghost" size="sm" className="w-full justify-start" onClick={logout}>
          Sign Out
        </Button>
      </div>
    </aside>
  );
}
