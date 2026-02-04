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
export type CellType = 'text' | 'status' | 'date' | 'datetime' | 'number' | 'currency';

export interface StatusConfig {
    activeText?: string;
    inactiveText?: string;
    activeClass?: string;
    inactiveClass?: string;
}

export interface TableColumn {
    key: string;
    label: string;
    sortable?: boolean;
    width?: string;
    align?: 'left' | 'center' | 'right';
    type?: CellType;
    statusConfig?: StatusConfig;
    emptyText?: string;
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

// DataTable Config Types
export interface CreateButtonConfig {
    label: string;
    permission?: string;
    action?: string;
}

export interface DeleteDialogConfig {
    title: string;
    message: string | ((row: any) => string);
    confirmText?: string;
    itemLabel?: string;
}

// Filter Types
export interface FilterField {
    key: string;
    label: string;
    type: 'text' | 'select' | 'date';
    options?: SelectOption[];
    placeholder?: string;
}

// Export Types
export interface ExportConfig {
    filename: string;
    title?: string;
    permission?: string;
}

export interface ExportInfo {
    search?: string;
    filters?: { label: string; value: string }[];
}

// Browse Types
export interface BrowseColumn {
    key: string;
    label: string;
    width?: string;
    align?: 'left' | 'center' | 'right';
    formatter?: (value: any, row: any) => string;
}

export interface BrowseConfig {
    endpoint: string;
    title: string;
    columns: BrowseColumn[];
    valueKey?: string;
    displayFormat: string | ((row: any) => string);
    /** Format for autocomplete dropdown items (same syntax as displayFormat) */
    dropdownFormat?: string | ((row: any) => string);
    showEndpoint?: string;
    /** Route to navigate for creating a new record (shows + button) */
    createRoute?: string;
    modalSize?: 'sm' | 'md' | 'lg' | 'xl' | 'full';
    perPage?: number;
    searchPlaceholder?: string;
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
