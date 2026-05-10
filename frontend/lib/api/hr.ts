import client from './client';

export interface DepartmentPayload {
  name: string;
  manager_id?: number | null;
}

export interface PositionPayload {
  name: string;
  department_id: number;
}

export interface EmployeePayload {
  name: string;
  national_id?: string | null;
  department_id?: number | null;
  position_id?: number | null;
  hire_date?: string | null;
  base_salary: number;
  status?: 'active' | 'inactive' | 'terminated';
}

export interface LeaveTypePayload {
  name: string;
  days_allowed_per_year: number;
}

export interface LeaveRequestPayload {
  employee_id: number;
  leave_type_id: number;
  from_date: string;
  to_date: string;
  notes?: string;
}

export interface RunPayrollPayload {
  month: number;
  year: number;
}

export const hrApi = {
  getDepartments: () => client.get('/hr/departments'),
  getDepartment:  (id: number) => client.get(`/hr/departments/${id}`),
  createDepartment: (data: DepartmentPayload) => client.post('/hr/departments', data),
  updateDepartment: (id: number, data: Partial<DepartmentPayload>) => client.put(`/hr/departments/${id}`, data),
  deleteDepartment: (id: number) => client.delete(`/hr/departments/${id}`),

  getPositions: () => client.get('/hr/positions'),
  getPosition:  (id: number) => client.get(`/hr/positions/${id}`),
  createPosition: (data: PositionPayload) => client.post('/hr/positions', data),
  updatePosition: (id: number, data: Partial<PositionPayload>) => client.put(`/hr/positions/${id}`, data),
  deletePosition: (id: number) => client.delete(`/hr/positions/${id}`),

  getEmployees: () => client.get('/hr/employees'),
  getEmployee:  (id: number) => client.get(`/hr/employees/${id}`),
  createEmployee: (data: EmployeePayload) => client.post('/hr/employees', data),
  updateEmployee: (id: number, data: Partial<EmployeePayload>) => client.put(`/hr/employees/${id}`, data),
  deleteEmployee: (id: number) => client.delete(`/hr/employees/${id}`),

  getAttendance: (params: { employee_id: number; month?: string }) =>
    client.get('/hr/attendance', { params }),
  clockIn:  (employee_id: number) => client.post('/hr/attendance/clock-in', { employee_id }),
  clockOut: (employee_id: number) => client.post('/hr/attendance/clock-out', { employee_id }),
  manualAttendance: (data: Record<string, unknown>) => client.post('/hr/attendance/manual', data),

  getLeaveTypes:  () => client.get('/hr/leaves/types'),
  createLeaveType: (data: LeaveTypePayload) => client.post('/hr/leaves/types', data),
  updateLeaveType: (id: number, data: LeaveTypePayload) => client.put(`/hr/leaves/types/${id}`, data),
  deleteLeaveType: (id: number) => client.delete(`/hr/leaves/types/${id}`),

  getLeaves:    () => client.get('/hr/leaves'),
  createLeave:  (data: LeaveRequestPayload) => client.post('/hr/leaves', data),
  approveLeave: (id: number) => client.put(`/hr/leaves/${id}/approve`, {}),
  rejectLeave:  (id: number) => client.put(`/hr/leaves/${id}/reject`, {}),

  getPayrolls: () => client.get('/hr/payroll'),
  getPayroll:  (id: number) => client.get(`/hr/payroll/${id}`),
  runPayroll:  (data: RunPayrollPayload) => client.post('/hr/payroll/run', data),
  approvePayroll: (id: number) => client.post(`/hr/payroll/${id}/approve`, {}),
  generatePayslip: (runId: number, empId: number) =>
    client.get(`/hr/payroll/${runId}/payslip/${empId}`),
};
