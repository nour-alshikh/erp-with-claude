'use client';

import { useState } from 'react';
import { useParams } from 'next/navigation';
import { useEmployee, useAttendance } from '@/lib/hooks/useHR';
import { formatCents } from '@/lib/utils/money';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default function EmployeeProfilePage() {
  const { id } = useParams<{ id: string }>();
  const [month, setMonth] = useState(() => new Date().toISOString().slice(0, 7));

  const { data: employee, isLoading } = useEmployee(Number(id));
  const { data: attendance } = useAttendance(Number(id), month);

  if (isLoading) return <p className="text-muted-foreground">Loading…</p>;
  if (!employee)  return <p>Employee not found.</p>;

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">{employee.name}</h1>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <Card>
          <CardHeader>
            <CardTitle>Details</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2 text-sm">
            {[
              ['National ID',  employee.national_id ?? '—'],
              ['Department',   employee.department?.name ?? '—'],
              ['Position',     employee.position?.name ?? '—'],
              ['Hire Date',    employee.hire_date ?? '—'],
              ['Base Salary',  formatCents(employee.base_salary)],
            ].map(([label, value]) => (
              <div key={label} className="flex justify-between">
                <span className="text-muted-foreground">{label}</span>
                <span>{value}</span>
              </div>
            ))}
            <div className="flex justify-between">
              <span className="text-muted-foreground">Status</span>
              <Badge variant={employee.status === 'active' ? 'default' : 'secondary'}>
                {employee.status}
              </Badge>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <CardTitle>Attendance</CardTitle>
              <input
                type="month"
                value={month}
                onChange={(e) => setMonth(e.target.value)}
                className="text-sm border rounded px-2 py-1"
              />
            </div>
          </CardHeader>
          <CardContent>
            <div className="space-y-1 text-sm max-h-64 overflow-y-auto">
              {attendance?.length ? (
                attendance.map((a: any) => (
                  <div
                    key={a.id}
                    className="flex justify-between items-center py-1 border-b last:border-0"
                  >
                    <span>{a.date}</span>
                    <div className="flex items-center gap-2">
                      <span className="text-muted-foreground text-xs">
                        {a.clock_in ?? '—'} – {a.clock_out ?? '—'}
                      </span>
                      <Badge variant={a.type === 'present' ? 'default' : 'secondary'} className="text-xs">
                        {a.type}
                      </Badge>
                    </div>
                  </div>
                ))
              ) : (
                <p className="text-muted-foreground">No records for this month.</p>
              )}
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
