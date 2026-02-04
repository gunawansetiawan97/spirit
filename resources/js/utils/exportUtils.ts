import * as XLSX from 'xlsx';
import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import type { TableColumn } from '@/types';

/**
 * Get nested value from object by dot-separated path
 */
const getNestedValue = (obj: any, path: string): any => {
    return path.split('.').reduce((current, key) => current?.[key], obj);
};

/**
 * Prepare export data: extract values from rows based on column definitions
 */
const prepareExportData = (data: any[], columns: TableColumn[]): { headers: string[]; rows: any[][] } => {
    const exportColumns = columns.filter(col => col.type !== 'status' || true); // include all columns
    const headers = exportColumns.map(col => col.label);
    const rows = data.map(row =>
        exportColumns.map(col => {
            const value = getNestedValue(row, col.key);
            if (col.type === 'status') {
                const activeText = col.statusConfig?.activeText ?? 'Aktif';
                const inactiveText = col.statusConfig?.inactiveText ?? 'Nonaktif';
                return value ? activeText : inactiveText;
            }
            if (col.formatter) {
                return col.formatter(value, row);
            }
            return value ?? '-';
        })
    );
    return { headers, rows };
};

/**
 * Export data to Excel (.xlsx) file
 */
export const exportToExcel = (data: any[], columns: TableColumn[], filename: string): void => {
    const { headers, rows } = prepareExportData(data, columns);

    const worksheetData = [headers, ...rows];
    const worksheet = XLSX.utils.aoa_to_sheet(worksheetData);

    // Auto-fit column widths
    const colWidths = headers.map((header, i) => {
        const maxLen = Math.max(
            header.length,
            ...rows.map(row => String(row[i] ?? '').length)
        );
        return { wch: Math.min(maxLen + 2, 50) };
    });
    worksheet['!cols'] = colWidths;

    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Data');
    XLSX.writeFile(workbook, `${filename}.xlsx`);
};

/**
 * Export data to PDF file
 */
export const exportToPdf = (data: any[], columns: TableColumn[], filename: string, title?: string): void => {
    const { headers, rows } = prepareExportData(data, columns);

    const doc = new jsPDF({
        orientation: rows.length > 0 && headers.length > 6 ? 'landscape' : 'portrait',
        unit: 'mm',
        format: 'a4',
    });

    // Title
    if (title) {
        doc.setFontSize(14);
        doc.text(title, 14, 15);
    }

    // Table
    autoTable(doc, {
        head: [headers],
        body: rows.map(row => row.map(cell => String(cell ?? ''))),
        startY: title ? 22 : 14,
        styles: {
            fontSize: 9,
            cellPadding: 3,
        },
        headStyles: {
            fillColor: [59, 130, 246], // primary blue
            textColor: 255,
            fontStyle: 'bold',
        },
        alternateRowStyles: {
            fillColor: [249, 250, 251], // gray-50
        },
    });

    doc.save(`${filename}.pdf`);
};
