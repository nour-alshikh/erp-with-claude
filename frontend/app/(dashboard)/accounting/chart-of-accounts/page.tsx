'use client';

import { useAccounts } from '@/lib/hooks/useAccounting';
import { Badge } from '@/components/ui/badge';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';

const TYPE_ORDER = ['asset', 'liability', 'equity', 'income', 'expense'] as const;

const typeLabel: Record<string, string> = {
  asset:     'Assets',
  liability: 'Liabilities',
  equity:    'Equity',
  income:    'Income',
  expense:   'Expenses',
};

export default function ChartOfAccountsPage() {
  const { data: accounts, isLoading } = useAccounts();

  const grouped = TYPE_ORDER.reduce<Record<string, any[]>>((acc, type) => {
    acc[type] = (accounts ?? []).filter((a: any) => a.type === type);
    return acc;
  }, {} as Record<string, any[]>);

  const rows = (type: string): any[] => {
    const parents = grouped[type].filter((a: any) => !a.parent_id);
    const result: any[] = [];
    const add = (account: any, depth: number) => {
      result.push({ ...account, depth });
      grouped[type]
        .filter((a: any) => a.parent_id === account.id)
        .forEach((child: any) => add(child, depth + 1));
    };
    parents.forEach((p: any) => add(p, 0));
    return result;
  };

  if (isLoading) return <p className="text-muted-foreground">Loading…</p>;

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">Chart of Accounts</h1>

      {TYPE_ORDER.map((type) => {
        const items = rows(type);
        if (!items.length) return null;

        return (
          <div key={type}>
            <h2 className="text-sm font-semibold uppercase tracking-wide text-muted-foreground mb-2">
              {typeLabel[type]}
            </h2>
            <div className="border rounded-lg overflow-hidden">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="w-28">Code</TableHead>
                    <TableHead>Name</TableHead>
                    <TableHead className="w-24">Status</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {items.map((account: any) => (
                    <TableRow key={account.id}>
                      <TableCell className="font-mono text-sm">{account.code}</TableCell>
                      <TableCell>
                        <span style={{ paddingLeft: `${account.depth * 20}px` }}>
                          {account.depth > 0 && (
                            <span className="text-muted-foreground mr-1">└</span>
                          )}
                          {account.name}
                        </span>
                      </TableCell>
                      <TableCell>
                        <Badge variant={account.is_active ? 'default' : 'secondary'}>
                          {account.is_active ? 'Active' : 'Inactive'}
                        </Badge>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          </div>
        );
      })}
    </div>
  );
}
