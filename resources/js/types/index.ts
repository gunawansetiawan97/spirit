// Form Types
export interface SelectOption {
    value: string | number;
    label: string;
    disabled?: boolean;
}

export interface ValidationRule {
    required?: boolean;
    min?: number;
    max?: number;
    minLength?: number;
    maxLength?: number;
    pattern?: RegExp;
    email?: boolean;
    custom?: (value: any) => boolean | string;
}

export interface FormField {
    name: string;
    value: any;
    error?: string;
    touched?: boolean;
    rules?: ValidationRule;
}

// Table Types
export interface TableColumn {
    key: string;
    label: string;
    sortable?: boolean;
    width?: string;
    align?: 'left' | 'center' | 'right';
    formatter?: (value: any, row: any) => string;
}

export interface TablePagination {
    currentPage: number;
    perPage: number;
    total: number;
    lastPage: number;
}

export interface SortConfig {
    key: string;
    direction: 'asc' | 'desc';
}

// Modal Types
export interface ModalConfig {
    title?: string;
    size?: 'sm' | 'md' | 'lg' | 'xl';
    closable?: boolean;
    closeOnEscape?: boolean;
    closeOnOverlay?: boolean;
}

export interface ConfirmDialogConfig {
    title: string;
    message: string;
    confirmText?: string;
    cancelText?: string;
    variant?: 'info' | 'warning' | 'danger';
}

// Action Button Types
export type ActionType = 'view' | 'edit' | 'delete' | 'permissions' | 'custom';

export interface ActionConfig {
    type: ActionType;
    permission?: string;
    action?: string;
    label?: string;
    icon?: string;
    color?: string;
    show?: (row: any) => boolean;
    onClick?: (row: any) => void;
}

// API Response Types
export interface ApiResponse<T = any> {
    data: T;
    message?: string;
    status: 'success' | 'error';
}

export interface PaginatedResponse<T = any> {
    data: T[];
    meta: {
        current_page: number;
        per_page: number;
        total: number;
        last_page: number;
    };
}
