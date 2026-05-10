'use client';

import Link from 'next/link';
import { useEmployees, useDeleteEmployee } from '@/lib/hooks/useHR';
import { formatCents } from '@/lib/utils/money';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { buttonVariants } from '@/components/ui/button';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { toast } from 'sonner';

const statusVariant: Record<string, 'default' | 'secondary' | 'destructive'> = {
  active:     'default',
  inactive:   'secondary',
  terminated: 'destructive',
};

export default function EmployeesPage() {
  const { data: employees, isLoading } = useEmployees();
  const deleteMutation = useDeleteEmployee();

  function handleDelete(id: number, name: string) {
    if (!confirm(`Delete ${name}?`)) return;
    deleteMutation.mutate(id, {
      onSuccess: () => toast.success('Employee deleted'),
      onError:   () => toast.error('Failed to delete employee'),
    });
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold">Employees</h1>
      </div>

      {isLoading ? (
        <p className="text-muted-foreground">Loading…</p>
      ) : (
        <div className="border rounded-lg overflow-hidden">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Department</TableHead>
                <TableHead>Position</TableHead>
                <TableHead>Base Salary</TableHead>
                <TableHead>Status</TableHead>
                <TableHead />
              </TableRow>
            </TableHeader>
            <TableBody>
              {employees?.length ? (
                employees.map((emp: any) => (
                  <TableRow key={emp.id}>
                    <TableCell className="font-medium">
                      <Link href={`/hr/employees/${emp.id}`} className="hover:underline">
                        {emp.name}
                      </Link>
                    </TableCell>
                    <TableCell>{emp.department?.name ?? '—'}</TableCell>
                    <TableCell>{emp.position?.name ?? '—'}</TableCell>
                    <TableCell>{formatCents(emp.base_salary)}</TableCell>
                    <TableCell>
                      <Badge variant={statusVariant[emp.status] ?? 'secondary'}>
                        {emp.status}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-right space-x-2">
                      <Link
                        href={`/hr/employees/${emp.id}`}
                        className={buttonVariants({ variant: 'ghost', size: 'sm' })}
                      >
                        View
                      </Link>
                      <Button
                        variant="ghost"
                        size="sm"
                        className="text-destructive hover:text-destructive"
                        onClick={() => handleDelete(emp.id, emp.name)}
                        disabled={deleteMutation.isPending}
                      >
                        Delete
                      </Button>
                    </TableCell>
                  </TableRow>
                ))
              ) : (
                <TableRow>
                  <TableCell colSpan={6} className="text-center text-muted-foreground py-8">
                    No employees found.
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </div>
      )}
    </div>
  );
}
