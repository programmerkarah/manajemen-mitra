<style>
    @page {
        margin: 20px;
    }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 11px;
        color: #111827;
        margin: 0;
    }
    h1 {
        font-size: 14px;
        margin: 0 0 4px 0;
        text-align: center;
        line-height: 1.35;
    }
    .title-line {
        display: block;
    }
    h2 {
        font-size: 12px;
        margin: 16px 0 6px 0;
        border-bottom: 1px solid #d1d5db;
        padding-bottom: 3px;
        page-break-after: avoid;
    }
    .meta {
        text-align: right;
        font-size: 10px;
        color: #6b7280;
        margin-bottom: 12px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
        page-break-inside: auto;
    }

    thead {
        display: table-header-group;
    }

    tfoot {
        display: table-row-group;
    }

    th, td {
        border: 1px solid #374151;
        padding: 4px 6px;
        vertical-align: top;
    }

    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
    th {
        background: #f3f4f6;
        text-align: center;
        font-weight: 700;
        font-size: 10px;
    }
    td.number {
        text-align: center;
    }
    td.amount, th.amount {
        text-align: right;
        white-space: nowrap;
    }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .font-bold { font-weight: 700; }
    .text-green { color: #16a34a; }
    .text-amber { color: #d97706; }
    .text-red { color: #dc2626; }
    .badge {
        display: inline-block;
        padding: 1px 6px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 600;
    }
    .badge-green { background: #dcfce7; color: #166534; }
    .badge-amber { background: #fef9c3; color: #854d0e; }
    .badge-red { background: #fee2e2; color: #991b1b; }
    .summary-grid {
        margin-bottom: 12px;
    }
    .summary-grid td {
        border: none;
        padding: 2px 8px 2px 0;
        font-size: 11px;
    }
    .summary-grid td.label {
        font-weight: 600;
        width: 180px;
    }
    .disclaimer {
        margin-top: 12px;
        font-size: 10px;
        color: #6b7280;
        page-break-inside: avoid;
        page-break-before: avoid;
    }
    .month-names {
        font-size: 10px;
    }

    .chart-block {
        margin-bottom: 10px;
        page-break-inside: avoid;
        text-align: center;
    }

    .chart-section {
        margin-bottom: 10px;
        page-break-inside: avoid;
    }

    .chart-section h2 {
        margin-bottom: 6px;
    }

    .section-block {
        margin-bottom: 10px;
        page-break-inside: avoid;
    }

    .section-block h2 {
        margin-bottom: 6px;
    }

    .table-section {
        margin-bottom: 10px;
        page-break-inside: auto;
    }

    .table-section.tight {
        page-break-inside: avoid;
    }

    .table-section.tight table {
        page-break-inside: avoid;
    }

    .closing-block {
        page-break-inside: avoid;
    }

    .page-break-before {
        page-break-before: always;
    }

    .chart-image {
        width: 94%;
        max-width: 760px;
        height: auto;
        display: block;
        margin: 0 auto;
    }

    .no-break {
        page-break-inside: avoid;
    }
</style>
