import AppLayout from '@/layouts/app-layout';
import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, Form } from '@inertiajs/react';
import { useCallback, useState } from 'react';
import { type BreadcrumbItem } from '@/types';
import { CalendarDays, CheckSquare, FileText } from 'lucide-react';

interface PpkInfo {
  nama: string;
  nip?: string | null;
}

interface KegiatanInfo {
  id: number;
  hashed_id: string;
  kode_kegiatan: string;
  nama_kegiatan: string;
  ketua_tim_nama?: string | null;
  ketua_tim_nip?: string | null;
}

interface PetugasItem {
  id: number;
  petugas_id: number;
  spk_id: number;
  nama_petugas: string;
  nomor_spk: string;
  peran?: string | null;
  hasil_listing?: number | null;
  satuan_listing?: string | null;
  hasil_pendataan_lapangan?: number | null;
  satuan_pendataan_lapangan?: string | null;
  hasil_pengolahan?: number | null;
  satuan_pengolahan?: string | null;
  catatan?: string | null;
}

interface CreateForKegiatanProps {
  kegiatan: KegiatanInfo;
  petugas_list: PetugasItem[];
  show_listing_columns?: boolean;
  show_pengolahan_columns?: boolean;
  ppk?: PpkInfo | null;
  status_periode: 'dikirim' | 'perubahan';
}

const peranLabel = (peran?: string | null): string => {
  if (!peran) return '-';
  const key = peran.toLowerCase();
  const map: Record<string, string> = {
    pengolahan: 'Petugas Pengolahan',
    pml: 'Petugas Pemeriksa Pemutakhiran / Lapangan',
    pemeriksa_pengolahan: 'Petugas Pemeriksa Pengolahan',
    pcl_ppl: 'Petugas Pencacahan Petmutakhiran / Lapangan',
  };
  return map[key] ?? peran;
};

const breadcrumbs = (keg: KegiatanInfo): BreadcrumbItem[] => ([
  { title: 'BAST', href: '/bast' },
  { title: keg.nama_kegiatan, href: `/bast/kegiatan/${keg.hashed_id}/create` },
]);

export default function CreateForKegiatan({ kegiatan, petugas_list, show_listing_columns = false, show_pengolahan_columns = false, ppk, status_periode }: CreateForKegiatanProps) {
  const anyPengolahan = petugas_list.some((p) => (p.peran ?? '').toLowerCase() === 'pengolahan');
  const [validationModalOpen, setValidationModalOpen] = useState(false);
  const [validationMessages, setValidationMessages] = useState<string[]>([]);

  const handlePreview = useCallback((event: React.MouseEvent<HTMLButtonElement>, idx?: number | null) => {
    event.preventDefault();

    const form = event.currentTarget.form;
    if (!form) return;
    // validate required fields first
    const msgs = validateAll(form);
    if (msgs.length > 0) {
      setValidationMessages(msgs);
      setValidationModalOpen(true);
      return;
    }

    const formData = new FormData(form);
    if (typeof idx === 'number' && idx >= 0) {
      formData.set('petugas_index', String(idx));
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const tempForm = document.createElement('form');
    tempForm.method = 'POST';
    tempForm.action = '/bast/preview';
    tempForm.target = '_blank';
    tempForm.style.display = 'none';

    if (csrf) {
      const tokenInput = document.createElement('input');
      tokenInput.type = 'hidden';
      tokenInput.name = '_token';
      tokenInput.value = csrf;
      tempForm.appendChild(tokenInput);
    }

    formData.forEach((value, key) => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = key;
      input.value = typeof value === 'string' ? value : '';
      tempForm.appendChild(input);
    });

    document.body.appendChild(tempForm);
    tempForm.submit();
    document.body.removeChild(tempForm);
  }, []);

  function validateAll(form: HTMLFormElement): string[] {
    const f = form as any as HTMLFormElement;
    const messages: string[] = [];

    const getVal = (name: string): string | null => {
      const el = (f.elements as any)[name];
      if (!el) return null;
      // handle NodeList
      if (el.value === undefined && el.length) {
        return (el[0].value ?? '').toString().trim();
      }
      return (el.value ?? '').toString().trim();
    };

    const tanggal = getVal('tanggal_bast');
    if (!tanggal) {
      messages.push('Tanggal BAST harus diisi.');
    }

    // global instruments: listing may be conditional
    const showListingInput = show_listing_columns || (anyPengolahan && show_pengolahan_columns);
    if (showListingInput) {
      const instrListing = getVal('instrumen_listing');
      if (!instrListing) {
        messages.push('Instrumen Listing harus diisi.');
      }
    }

    const instrPendataan = getVal('instrumen_pendataan_lapangan');
    if (!instrPendataan) {
      messages.push('Instrumen Pendataan / Lapangan harus diisi.');
    }

    // Validate each petugas visible inputs (except catatan)
    for (let i = 0; i < petugas_list.length; i++) {
      const nameBase = `petugas[${i}]`;

      const fieldsToCheck = [
        `${nameBase}[hasil_listing]`,
        `${nameBase}[satuan_listing]`,
        `${nameBase}[hasil_pendataan_lapangan]`,
        `${nameBase}[satuan_pendataan_lapangan]`,
        `${nameBase}[hasil_pengolahan]`,
        `${nameBase}[satuan_pengolahan]`,
      ];

      for (const fname of fieldsToCheck) {
        const el = (f.elements as any)[fname];
        if (!el) continue; // field not present in this layout/row
        const val = (el.value ?? '').toString().trim();
        if (val === '') {
          const petugasName = petugas_list[i].nama_petugas ?? `#${i+1}`;
          if (fname.includes('hasil_')) {
            messages.push(`Isi jumlah ${fname.replace(`${nameBase}[`, '').replace(']', '')} untuk ${petugasName}.`);
          } else if (fname.includes('satuan')) {
            messages.push(`Isi satuan ${fname.replace(`${nameBase}[`, '').replace(']', '')} untuk ${petugasName}.`);
          } else {
            messages.push(`Lengkapi field ${fname} untuk ${petugasName}.`);
          }
        }
      }
    }

    return messages;
  }

  return (
    <AppLayout breadcrumbs={breadcrumbs(kegiatan)}>
      <Head title={`Buat BAST - ${kegiatan.nama_kegiatan}`} />

      <div className="space-y-6">
        <PageHeader
          title={`Buat BAST — ${kegiatan.nama_kegiatan}`}
          description={`Periode ${status_periode === 'perubahan' ? 'Perubahan' : 'Dikirim'} • Ketua Tim: ${kegiatan.ketua_tim_nama ?? '-'}`}
        />

        <ContentCard>
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <div className="text-sm font-medium text-neutral-700 dark:text-neutral-300">Kegiatan</div>
              <div className="text-neutral-900 dark:text-neutral-100">{kegiatan.nama_kegiatan}</div>
              <div className="text-sm text-neutral-500 dark:text-neutral-400">{kegiatan.kode_kegiatan}</div>
            </div>
            <div>
              <div className="text-sm font-medium text-neutral-700 dark:text-neutral-300">PPK</div>
              <div className="text-neutral-900 dark:text-neutral-100">{ppk?.nama ?? 'Tidak ada data PPK aktif'}</div>
              <div className="text-sm text-neutral-500 dark:text-neutral-400">NIP: {ppk?.nip ?? '-'}</div>
            </div>
          </div>
        </ContentCard>

        <ContentCard>
          <Form action="/bast" method="post" onSubmit={(e: any) => {
            const form = e.currentTarget as HTMLFormElement;
            const msgs = validateAll(form);
            if (msgs.length > 0) {
              e.preventDefault();
              setValidationMessages(msgs);
              setValidationModalOpen(true);
            }
          }}>
            {({ processing, wasSuccessful }) => (
              <div className="space-y-6">
                {/* Hidden core fields */}
                <input type="hidden" name="kegiatan_id" value={kegiatan.id} />

                {/* Tanggal BAST */}
                <div>
                  <label className="mb-1 block text-sm font-medium text-neutral-700 dark:text-neutral-300">
                    Tanggal BAST
                  </label>
                  <div className="relative max-w-sm">
                    <CalendarDays className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-500" />
                    <Input type="date" name="tanggal_bast" required className="pl-9" />
                  </div>
                </div>

                {/* FASIH */}
                <div>
                  <label className="inline-flex items-center gap-2 text-sm font-medium text-neutral-700 dark:text-neutral-300">
                    <CheckSquare className="h-4 w-4" />
                    <span>Gunakan klausul penghapusan Aplikasi FASIH</span>
                  </label>
                  <div className="mt-2">
                    <input type="hidden" name="menggunakan_fasih" value="0" />
                    <input type="checkbox" name="menggunakan_fasih" value="1" className="h-4 w-4" />
                  </div>
                </div>

                {/* Lampiran Petugas */}
                <div>
                  <div className="mb-3 flex items-center gap-2 text-sm font-medium text-neutral-700 dark:text-neutral-300">
                    <FileText className="h-4 w-4" />
                    Lampiran — Rincian Petugas dan Hasil Pekerjaan
                  </div>
                  {(() => {
                    const showListingInput = show_listing_columns || (anyPengolahan && show_pengolahan_columns);
                    const showPendataanInput = true; // Hasil pendataan (or pengolahan lapangan) column is always shown in one of the table layouts
                    return (
                      <div className="grid grid-cols-2 gap-4 mb-4">
                        {showListingInput && (
                          <div>
                            <div className="text-xs text-neutral-500">Instrumen Listing</div>
                            <Input name="instrumen_listing" placeholder="Nama instrumen untuk listing" />
                          </div>
                        )}
                        {showPendataanInput && (
                          <div>
                            <div className="text-xs text-neutral-500">Instrumen Pendataan / Lapangan</div>
                            <Input name="instrumen_pendataan_lapangan" placeholder="Nama instrumen untuk pendataan/lapangan" />
                          </div>
                        )}
                      </div>
                    );
                  })()}
                  <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                          <thead className="bg-neutral-50 dark:bg-neutral-800">
                        <tr>
                          <th className="px-3 py-2 text-left text-xs font-semibold uppercase">No</th>
                          <th className="px-3 py-2 text-left text-xs font-semibold uppercase">Nama Petugas</th>
                          <th className="px-3 py-2 text-left text-xs font-semibold uppercase">Nomor SPK</th>
                          {anyPengolahan && show_pengolahan_columns ? (
                            <>
                              <th className="px-3 py-2 text-left text-xs font-semibold uppercase">Hasil Pengolahan Listing</th>
                              <th className="px-3 py-2 text-left text-xs font-semibold uppercase">Hasil Pengolahan Lapangan</th>
                            </>
                          ) : (
                            <>
                                  {show_listing_columns && (
                                    <>
                                      <th className="px-3 py-2 text-left text-xs font-semibold uppercase">Hasil Listing</th>
                                    </>
                                  )}
                                  <th className="px-3 py-2 text-left text-xs font-semibold uppercase">Hasil Pendataan</th>
                              {show_pengolahan_columns && (
                                <th className="px-3 py-2 text-left text-xs font-semibold uppercase">Hasil Pengolahan</th>
                              )}
                            </>
                          )}
                          <th className="px-3 py-2 text-left text-xs font-semibold uppercase">Catatan</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                          {petugas_list.length === 0 ? (
                          <tr>
                            <td colSpan={7} className="px-6 py-8 text-center text-neutral-500">
                              Tidak ada petugas dengan SPK untuk periode ini.
                            </td>
                          </tr>
                        ) : (
                          petugas_list.map((p, idx) => (
                            <tr key={p.id}>
                              <td className="px-3 py-2 text-sm">{idx + 1}</td>
                              <td className="px-3 py-2 text-sm">
                                <div className="font-medium">{p.nama_petugas}</div>
                                <div className="text-xs text-neutral-500">{peranLabel(p.peran)}</div>
                                {/* Hidden required fields */}
                                <input type="hidden" name={`petugas[${idx}][petugas_id]`} value={p.petugas_id} />
                                <input type="hidden" name={`petugas[${idx}][spk_id]`} value={p.spk_id} />
                                <input type="hidden" name={`petugas[${idx}][nomor_spk]`} value={p.nomor_spk} />
                                <input type="hidden" name={`petugas[${idx}][nama_petugas]`} value={p.nama_petugas} />
                              </td>
                              <td className="px-3 py-2 text-sm">{p.nomor_spk}</td>

                              {/* Special case: petugas pengolahan -> show Listing + Lapangan pengolahan columns */}
                              {anyPengolahan && show_pengolahan_columns ? (
                                (p.peran ?? '').toLowerCase() === 'pengolahan' ? (
                                  <>
                                    <td className="px-3 py-2 text-sm">
                                      <div className="flex gap-2">
                                        <Input
                                          type="number"
                                          min="0"
                                          step="1"
                                          name={`petugas[${idx}][hasil_listing]`}
                                          defaultValue={p.hasil_listing ?? ''}
                                          placeholder="Jumlah"
                                          className="w-24"
                                          readOnly
                                        />
                                        <Input
                                          type="text"
                                          name={`petugas[${idx}][satuan_listing]`}
                                          defaultValue={p.satuan_listing ?? ''}
                                          placeholder="Satuan"
                                          className="w-28"
                                          readOnly
                                        />
                                        
                                      </div>
                                    </td>
                                    <td className="px-3 py-2 text-sm">
                                      <div className="flex gap-2">
                                        <Input
                                          type="number"
                                          min="0"
                                          step="1"
                                          name={`petugas[${idx}][hasil_pendataan_lapangan]`}
                                          defaultValue={p.hasil_pendataan_lapangan ?? ''}
                                          placeholder="Jumlah"
                                          className="w-24"
                                          readOnly
                                        />
                                        <Input
                                          type="text"
                                          name={`petugas[${idx}][satuan_pendataan_lapangan]`}
                                          defaultValue={p.satuan_pendataan_lapangan ?? ''}
                                          placeholder="Satuan"
                                          className="w-28"
                                          readOnly
                                        />
                                        
                                      </div>
                                    </td>
                                  </>
                                ) : (
                                  // Non-pengolahan rows: keep existing columns
                                  <>
                                    {show_listing_columns && (
                                      <td className="px-3 py-2 text-sm">
                                        <div className="flex gap-2">
                                          <Input
                                            type="number"
                                            min="0"
                                            step="1"
                                            name={`petugas[${idx}][hasil_listing]`}
                                            defaultValue={p.hasil_listing ?? ''}
                                            placeholder="Jumlah"
                                            className="w-24"
                                            readOnly
                                          />
                                          <Input
                                            type="text"
                                            name={`petugas[${idx}][satuan_listing]`}
                                            defaultValue={p.satuan_listing ?? ''}
                                            placeholder="Satuan"
                                            className="w-28"
                                            readOnly
                                          />
                                            
                                        </div>
                                      </td>
                                    )}
                                    <td className="px-3 py-2 text-sm">
                                      <div className="flex gap-2">
                                        <Input
                                          type="number"
                                          min="0"
                                          step="1"
                                          name={`petugas[${idx}][hasil_pendataan_lapangan]`}
                                          defaultValue={p.hasil_pendataan_lapangan ?? ''}
                                          placeholder="Jumlah"
                                          className="w-24"
                                          readOnly
                                        />
                                        <Input
                                          type="text"
                                          name={`petugas[${idx}][satuan_pendataan_lapangan]`}
                                          defaultValue={p.satuan_pendataan_lapangan ?? ''}
                                          placeholder="Satuan"
                                          className="w-28"
                                          readOnly
                                        />
                                          
                                      </div>
                                    </td>
                                    {show_pengolahan_columns && (
                                      <td className="px-3 py-2 text-sm">
                                        <div className="flex gap-2">
                                          <Input
                                            type="number"
                                            min="0"
                                            step="1"
                                            name={`petugas[${idx}][hasil_pengolahan]`}
                                            defaultValue={p.hasil_pengolahan ?? ''}
                                            placeholder="Jumlah"
                                            className="w-24"
                                            readOnly
                                          />
                                          <Input
                                            type="text"
                                            name={`petugas[${idx}][satuan_pengolahan]`}
                                            defaultValue={p.satuan_pengolahan ?? ''}
                                            placeholder="Satuan"
                                            className="w-28"
                                            readOnly
                                          />
                                        </div>
                                      </td>
                                    )}
                                  </>
                                )
                              ) : (
                                // Original layout when special pengolahan headers not used
                                <>
                                  {show_listing_columns && (
                                    <td className="px-3 py-2 text-sm">
                                      <div className="flex gap-2">
                                        <Input
                                          type="number"
                                          min="0"
                                          step="1"
                                          name={`petugas[${idx}][hasil_listing]`}
                                          defaultValue={p.hasil_listing ?? ''}
                                          placeholder="Jumlah"
                                          className="w-24"
                                          readOnly
                                        />
                                        <Input
                                          type="text"
                                          name={`petugas[${idx}][satuan_listing]`}
                                          defaultValue={p.satuan_listing ?? ''}
                                          placeholder="Satuan"
                                          className="w-28"
                                          readOnly
                                        />
                                      </div>
                                    </td>
                                  )}
                                  <td className="px-3 py-2 text-sm">
                                    <div className="flex gap-2">
                                      <Input
                                        type="number"
                                        min="0"
                                        step="1"
                                        name={`petugas[${idx}][hasil_pendataan_lapangan]`}
                                        defaultValue={p.hasil_pendataan_lapangan ?? ''}
                                        placeholder="Jumlah"
                                        className="w-24"
                                        readOnly
                                      />
                                      <Input
                                        type="text"
                                        name={`petugas[${idx}][satuan_pendataan_lapangan]`}
                                        defaultValue={p.satuan_pendataan_lapangan ?? ''}
                                        placeholder="Satuan"
                                        className="w-28"
                                        readOnly
                                      />
                                      
                                    </div>
                                  </td>
                                  {show_pengolahan_columns && (
                                    <td className="px-3 py-2 text-sm">
                                      <div className="flex gap-2">
                                        <Input
                                          type="number"
                                          min="0"
                                          step="1"
                                          name={`petugas[${idx}][hasil_pengolahan]`}
                                          defaultValue={p.hasil_pengolahan ?? ''}
                                          placeholder="Jumlah"
                                          className="w-24"
                                          readOnly
                                        />
                                        <Input
                                          type="text"
                                          name={`petugas[${idx}][satuan_pengolahan]`}
                                          defaultValue={p.satuan_pengolahan ?? ''}
                                          placeholder="Satuan"
                                          className="w-28"
                                          readOnly
                                        />
                                      </div>
                                    </td>
                                  )}
                                </>
                              )}
                              {/* Catatan */}
                              <td className="px-3 py-2 text-sm">
                                <Input
                                  type="text"
                                  name={`petugas[${idx}][catatan]`}
                                  defaultValue={p.catatan ?? ''}
                                  placeholder="Catatan opsional"
                                />
                              </td>
                              {/* Aksi */}
                            </tr>
                          ))
                        )}
                      </tbody>
                    </table>
                  </div>
                </div>

                {/* Actions */}
                <div className="flex items-center justify-end gap-2">
                  <Link href="/bast">
                    <Button variant="outline">Kembali</Button>
                  </Link>
                  <Button
                    type="button"
                    variant="outline"
                    onClick={(e) => handlePreview(e as any, null)}
                  >
                    Preview PDF
                  </Button>
                  <Button
                    type="submit"
                    disabled={processing}
                  >
                    {processing ? 'Menyimpan...' : 'Buat BAST'}
                  </Button>
                </div>

                {wasSuccessful && (
                  <div className="text-sm text-green-700">BAST berhasil dibuat.</div>
                )}
              </div>
            )}
          </Form>
          {validationModalOpen && (
            <div className="fixed inset-0 z-50 flex items-center justify-center">
              <div className="absolute inset-0 bg-black/50" onClick={() => setValidationModalOpen(false)} />
              <div className="relative z-10 w-full max-w-xl rounded bg-white p-6 shadow-lg dark:bg-neutral-800">
                <h3 className="mb-3 text-lg font-semibold">Perbaiki input yang diperlukan</h3>
                <div className="mb-4 max-h-60 overflow-auto text-sm">
                  <ul className="list-disc list-inside space-y-1 text-neutral-800 dark:text-neutral-200">
                    {validationMessages.map((m, i) => (
                      <li key={i}>{m}</li>
                    ))}
                  </ul>
                </div>
                <div className="flex justify-end">
                  <Button variant="ghost" onClick={() => setValidationModalOpen(false)}>Tutup</Button>
                </div>
              </div>
            </div>
          )}
        </ContentCard>
      </div>
    </AppLayout>
  );
}
