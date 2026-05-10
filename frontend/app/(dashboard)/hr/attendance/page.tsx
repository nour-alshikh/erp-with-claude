'use client';

import { useState } from 'react';
import { useEmployees, useAttendance } from '@/lib/hooks/useHR';
import { Badge } from '@/components/ui/badge';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';

export default function AttendancePage() {
  const [employeeId, setEmployeeId] = useState<number | null>(null);
  const [month, setMonth] = useState(() => new Date().toISOString().slice(0, 7));

  const { data: employees } = useEmployees();
  const { data: attendance, isLoading } = useAttendance(employeeId ?? 0, month);

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-bold">Attendance</h1>

      <div className="flex gap-3 items-center">
        <Select onValueChange={(v) => setEmployeeId(Number(v))}>
          <SelectTrigger className="w-56">
            <SelectValue placeholder="Select employee" />
          </SelectTrigger>
          <SelectContent>
            {employees?.map((emp: any) => (
              <SelectItem key={emp.id} value={String(emp.id)}>
                {emp.name}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>

        <input
          type="month"
          value={month}
          onChange={(e) => setMonth(e.target.value)}
          className="border rounded px-3 py-2 text-sm"
        />
      </div>

      {!employeeId ? (
        <p className="text-muted-foreground text-sm">Select an employee to view attendance.</p>
      ) : isLoading ? (
        <p className="text-muted-foreground">Loading…</p>
      ) : (
        <div className="border rounded-lg overflow-hidden">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Date</TableHead>
                <TableHead>Clock In</TableHead>
                <TableHead>Clock Out</TableHead>
                <TableHead>Type</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {attendance?.length ? (
                attendance.map((a: any) => (
                  <TableRow key={a.id}>
                    <TableCell>{a.date}</TableCell>
                    <TableCell>{a.clock_in ?? '—'}</TableCell>
                    <TableCell>{a.clock_out ?? '—'}</TableCell>
                    <TableCell>
                      <Badge variant={a.type === 'present' ? 'default' : 'secondary'}>
                        {a.type}
                      </Badge>
                    </TableCell>
                  </TableRow>
                ))
              ) : (
                <TableRow>
                  <TableCell colSpan={4} className="text-center text-muted-foreground py-8">
                    No records for this period.
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
