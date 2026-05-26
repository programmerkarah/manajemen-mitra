<style>
    @page {
        margin: 14px;
    }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 11px;
        color: #111827;
        margin: 0;
        line-height: 1.35;
    }
    h1 {
        font-size: 14px;
        margin: 0 0 3px 0;
        text-align: center;
        line-height: 1.3;
    }
    .title-line {
        display: block;
    }
    h2 {
        font-size: 12px;
        margin: 10px 0 4px 0;
        border-bottom: 1px solid #d1d5db;
        padding-bottom: 3px;
        page-break-after: avoid;
    }
    .meta {
        text-align: right;
        font-size: 10px;
        color: #6b7280;
        margin-bottom: 8px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
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
        padding: 3px 5px;
        vertical-align: top;
    }

    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
    th {
        background: #eef2f7;
        text-align: center;
        font-weight: 700;
        font-size: 10px;
    }
    .striped tbody tr:nth-child(even) {
        background: #f8fafc;
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
        margin-bottom: 8px;
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
        margin-top: 8px;
        font-size: 10px;
        color: #6b7280;
        page-break-inside: avoid;
        page-break-before: avoid;
    }
    .month-names {
        font-size: 10px;
    }

    .chart-block {
        margin-bottom: 6px;
        page-break-inside: avoid;
        text-align: center;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 6px;
        background: #f8fafc;
    }

    .chart-section {
        margin-bottom: 6px;
        page-break-inside: avoid;
    }

    .chart-section h2 {
        margin-bottom: 4px;
    }

    .section-block {
        margin-bottom: 6px;
        page-break-inside: avoid;
    }

    .section-block h2 {
        margin-bottom: 4px;
    }

    .table-section {
        margin-bottom: 6px;
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
        width: 100%;
        max-width: 760px;
        height: auto;
        display: block;
        margin: 0 auto;
    }

    .no-break {
        page-break-inside: avoid;
    }

    .panel-grid {
        width: 100%;
        border-collapse: separate;
        border-spacing: 8px;
        margin-bottom: 8px;
    }

    .panel-grid td {
        border: none;
        padding: 0;
        width: 50%;
        vertical-align: top;
    }

    .panel-card {
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 6px;
        background: #ffffff;
    }

    .panel-title {
        font-size: 11px;
        font-weight: 700;
        color: #111827;
        margin: 0 0 4px 0;
        padding-bottom: 3px;
        border-bottom: 1px solid #e5e7eb;
    }

    .panel-card table {
        margin-bottom: 0;
    }

    .chart-grid {
        width: 100%;
        border-collapse: separate;
        border-spacing: 8px;
        margin-bottom: 8px;
    }

    .chart-grid td {
        border: none;
        padding: 0;
        vertical-align: top;
    }

    .chart-grid-two td {
        width: 50%;
    }

    .chart-grid-three td {
        width: 33.33%;
    }

    .chart-grid .chart-block {
        margin-bottom: 0;
    }
</style>
