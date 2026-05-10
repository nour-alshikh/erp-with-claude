'use client';

import { useLeaves, useApproveLeave, useRejectLeave } from '@/lib/hooks/useHR';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
  pending:  'secondary',
  approved: 'default',
  rejected: 'destructive',
};

export default function LeavesPage() {
  const { data: leaves, isLoading } = useLeaves();
  const approveMutation = useApproveLeave();
  const rejectMutation  = useRejectLeave();

  function handleApprove(id: number) {
    approveMutation.mutate(id, {
      onSuccess: () => toast.success('Leave approved'),
      onError:   () => toast.error('Failed to approve'),
    });
  }

  function handleReject(id: number) {
    rejectMutation.mutate(id, {
      onSuccess: () => toast.success('Leave rejected'),
      onError:   () => toast.error('Failed to reject'),
    });
  }

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-bold">Leave Requests</h1>

      {isLoading ? (
        <p className="text-muted-foreground">Loading…</p>
      ) : (
        <div className="border rounded-lg overflow-hidden">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Employee</TableHead>
                <TableHead>Type</TableHead>
                <TableHead>From</TableHead>
                <TableHead>To</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Notes</TableHead>
                <TableHead />
              </TableRow>
            </TableHeader>
            <TableBody>
              {leaves?.length ? (
                leaves.map((leave: any) => (
                  <TableRow key={leave.id}>
                    <TableCell className="font-medium">{leave.employee?.name ?? '—'}</TableCell>
                    <TableCell>{leave.leave_type?.name ?? '—'}</TableCell>
                    <TableCell>{leave.from_date}</TableCell>
                    <TableCell>{leave.to_date}</TableCell>
                    <TableCell>
                      <Badge variant={statusVariant[leave.status] ?? 'secondary'}>
                        {leave.status}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-muted-foreground text-sm max-w-[160px] truncate">
                      {leave.notes ?? '—'}
                    </TableCell>
                    <TableCell>
                      {leave.status === 'pending' && (
                        <div className="flex gap-2">
                          <Button
                            size="sm"
                            onClick={() => handleApprove(leave.id)}
                            disabled={approveMutation.isPending}
                          >
                            Approve
                          </Button>
                          <Button
                            size="sm"
                            variant="destructive"
                            onClick={() => handleReject(leave.id)}
                            disabled={rejectMutation.isPending}
                          >
                            Reject
                          </Button>
                        </div>
                      )}
                    </TableCell>
                  </TableRow>
                ))
              ) : (
                <TableRow>
                  <TableCell colSpan={7} className="text-center text-muted-foreground py-8">
                    No leave requests.
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
