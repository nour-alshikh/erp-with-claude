export interface Department {
  id: number;
  name: string;
  manager_id: number | null;
}

export interface Position {
  id: number;
  name: string;
  department_id: number;
}

export interface Employee {
  id: number;
  name: string;
  national_id: string;
  department_id: number;
  position_id: number;
  hire_date: string;
  base_salary: number; // cents
  status: 'active' | 'inactive' | 'terminated';
}

export interface LeaveRequest {
  id: number;
  employee_id: number;
  leave_type_id: number;
  from_date: string;
  to_date: string;
  status: 'pending' | 'approved' | 'rejected';
  approved_by: number | null;
  notes: string | null;
}

export interface PayrollRun {
  id: number;
  month: number;
  year: number;
  status: 'draft' | 'approved' | 'paid';
}

export interface PayrollItem {
  id: number;
  payroll_run_id: number;
  employee_id: number;
  type: 'earning' | 'deduction';
  description: string;
  amount: number; // cents
}
