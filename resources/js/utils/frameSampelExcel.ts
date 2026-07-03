const getCsrfToken = (): string => {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') || ''
    );
};

export interface FrameSampelMetadataColumnPayload {
    code: string;
    label: string;
    description: string;
    mode?: 'code_name' | 'code_only' | 'name_only';
}

export type FrameSampelSamplingMode = 'targeted' | 'purpossive';

export interface FrameSampelUnitSampelOption {
    id: number;
    nama: string;
}

export interface FrameSampelImportPreviewRow {
    nama_target?: string;
    sample_role?: string;
    target_unit_sampel: Record<string, string>;
    identitas_tambahan: Record<string, string>;
}

interface FrameSampelImportPreviewResponse {
    rows: FrameSampelImportPreviewRow[];
    errors: string[];
    summary: {
        total_rows: number;
        valid_rows: number;
        error_count: number;
    };
}

export const downloadFrameSampelTemplate = async (
    metadata: FrameSampelMetadataColumnPayload[],
    unitSampelList: FrameSampelUnitSampelOption[] = [],
    metodeSampling: FrameSampelSamplingMode = 'targeted',
    templateRows: Array<Record<string, unknown>> = [],
): Promise<void> => {
    const formData = new FormData();
    formData.append('metadata', JSON.stringify(metadata));
    formData.append('unit_sampel', JSON.stringify(unitSampelList));
    formData.append('metode_sampling', metodeSampling);
    formData.append('template_rows', JSON.stringify(templateRows));

    const response = await fetch('/kegiatan/frame-sampel/template', {
        method: 'POST',
        credentials: 'include',
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/octet-stream,*/*',
        },
        body: formData,
    });

    if (!response.ok) {
        throw new Error('Gagal menghasilkan template Excel frame sampel.');
    }

    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'detail-frame-sampel-template.xlsx';
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
};

export const importFrameSampelPreview = async (
    file: File,
    metadata: FrameSampelMetadataColumnPayload[],
    unitSampelList: FrameSampelUnitSampelOption[] = [],
    metodeSampling: FrameSampelSamplingMode = 'targeted',
): Promise<FrameSampelImportPreviewResponse> => {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('metadata', JSON.stringify(metadata));
    formData.append('unit_sampel', JSON.stringify(unitSampelList));
    formData.append('metode_sampling', metodeSampling);

    const response = await fetch('/kegiatan/frame-sampel/import-preview', {
        method: 'POST',
        credentials: 'include',
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        },
        body: formData,
    });

    const payload =
        (await response.json()) as Partial<FrameSampelImportPreviewResponse> & {
            message?: string;
            errors?: Record<string, string[]> | string[];
        };

    if (!response.ok) {
        const message = Array.isArray(payload.errors)
            ? payload.errors[0]
            : payload.errors && 'file' in payload.errors
              ? payload.errors.file?.[0]
              : payload.message;

        throw new Error(message || 'Gagal membaca file import frame sampel.');
    }

    return {
        rows: payload.rows || [],
        errors: Array.isArray(payload.errors) ? payload.errors : [],
        summary: payload.summary || {
            total_rows: 0,
            valid_rows: 0,
            error_count: 0,
        },
    };
};
