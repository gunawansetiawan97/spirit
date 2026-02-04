import * as XLSX from 'xlsx';
import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import type { TableColumn, ExportInfo } from '@/types';

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
    const headers = columns.map(col => col.label);
    const rows = data.map(row =>
        columns.map(col => {
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
 * Build info lines from search and active filters
 */
const buildInfoLines = (info?: ExportInfo): string[] => {
    const lines: string[] = [];
    if (info?.search) {
        lines.push(`Pencarian: ${info.search}`);
    }
    if (info?.filters && info.filters.length > 0) {
        const filterText = info.filters.map(f => `${f.label}: ${f.value}`).join(', ');
        lines.push(`Filter: ${filterText}`);
    }
    return lines;
};

/**
 * Export data to Excel (.xlsx) file
 */
export const exportToExcel = (data: any[], columns: TableColumn[], filename: string, title?: string, info?: ExportInfo): void => {
    const { headers, rows } = prepareExportData(data, columns);
    const infoLines = buildInfoLines(info);

    const sheetData: any[][] = [];

    // Title row
    if (title) {
        sheetData.push([title]);
        sheetData.push([]); // empty row
    }

    // Info rows (search & filter)
    if (infoLines.length > 0) {
        for (const line of infoLines) {
            sheetData.push([line]);
        }
        sheetData.push([]); // empty row
    }

    // Header and data
    sheetData.push(headers);
    sheetData.push(...rows);

    const worksheet = XLSX.utils.aoa_to_sheet(sheetData);

    // Auto-fit column widths
    const colWidths = headers.map((header, i) => {
        const maxLen = Math.max(
            header.length,
            ...rows.map(row => String(row[i] ?? '').length)
        );
        return { wch: Math.min(maxLen + 2, 50) };
    });
    worksheet['!cols'] = colWidths;

    // Merge title row across all columns
    if (title) {
        worksheet['!merges'] = [
            { s: { r: 0, c: 0 }, e: { r: 0, c: headers.length - 1 } },
        ];
    }

    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Data');
    XLSX.writeFile(workbook, `${filename}.xlsx`);
};

/**
 * Export data to PDF and open preview in new tab
 */
export const exportToPdf = (data: any[], columns: TableColumn[], filename: string, title?: string, info?: ExportInfo): void => {
    const { headers, rows } = prepareExportData(data, columns);
    const infoLines = buildInfoLines(info);

    const doc = new jsPDF({
        orientation: headers.length > 6 ? 'landscape' : 'portrait',
        unit: 'mm',
        format: 'a4',
    });

    let currentY = 14;

    // Title
    if (title) {
        doc.setFontSize(14);
        doc.text(title, 14, currentY);
        currentY += 8;
    }

    // Info lines (search & filter)
    if (infoLines.length > 0) {
        doc.setFontSize(9);
        doc.setTextColor(100);
        for (const line of infoLines) {
            doc.text(line, 14, currentY);
            currentY += 5;
        }
        doc.setTextColor(0);
        currentY += 2;
    }

    // Table
    autoTable(doc, {
        head: [headers],
        body: rows.map(row => row.map(cell => String(cell ?? ''))),
        startY: currentY,
        styles: {
            fontSize: 9,
            cellPadding: 3,
        },
        headStyles: {
            fillColor: [59, 130, 246],
            textColor: 255,
            fontStyle: 'bold',
        },
        alternateRowStyles: {
            fillColor: [249, 250, 251],
        },
    });

    // Open preview in new tab
    const pdfBlob = doc.output('blob');
    const blobUrl = URL.createObjectURL(pdfBlob);
    window.open(blobUrl, '_blank');
};
