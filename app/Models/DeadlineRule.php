<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class DeadlineRule extends Model
{
    protected $table = 'deadline_rules';

    protected $fillable = [
        'key',
        'feature_key',
        'action_key',
        'label',
        'description',
        'deadline_at',
        'cutoff_day',
        'is_enforced',
        'allow_manual_bypass',
        'scope_type',
        'sort_order',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'deadline_at' => 'datetime',
            'cutoff_day' => 'integer',
            'is_enforced' => 'boolean',
            'allow_manual_bypass' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return array<int, array{key:string,feature_key:string,action_key:string,label:string,description:string,scope_type:string,sort_order:int,cutoff_day:int}>
     */
    public static function defaultDefinitions(): array
    {
        return [
            [
                'key' => 'alokasi.manage',
                'feature_key' => 'alokasi',
                'action_key' => 'manage',
                'label' => 'Alokasi Petugas (Tambah/Edit/Simpan/Kirim)',
                'description' => 'Batas waktu pengelolaan alokasi petugas (tidak termasuk revisi).',
                'scope_type' => 'monthly',
                'sort_order' => 10,
                'cutoff_day' => 25,
            ],
            [
                'key' => 'alokasi.revisi',
                'feature_key' => 'alokasi',
                'action_key' => 'revisi',
                'label' => 'Revisi Alokasi Petugas',
                'description' => 'Batas waktu proses revisi alokasi petugas.',
                'scope_type' => 'monthly',
                'sort_order' => 15,
                'cutoff_day' => 25,
            ],
            [
                'key' => 'pengajuan_pulsa.manage',
                'feature_key' => 'pengajuan_pulsa',
                'action_key' => 'manage',
                'label' => 'Pengajuan Pulsa',
                'description' => 'Batas waktu pengajuan dan review pulsa.',
                'scope_type' => 'monthly',
                'sort_order' => 20,
                'cutoff_day' => 25,
            ],
            [
                'key' => 'sk.manage',
                'feature_key' => 'sk',
                'action_key' => 'manage',
                'label' => 'SK',
                'description' => 'Batas waktu pembuatan dan perubahan SK KPA.',
                'scope_type' => 'monthly',
                'sort_order' => 30,
                'cutoff_day' => 25,
            ],
            [
                'key' => 'spk.manage',
                'feature_key' => 'spk',
                'action_key' => 'manage',
                'label' => 'Perjanjian Kerja',
                'description' => 'Batas waktu pembuatan Perjanjian Kerja/Kontrak Petugas.',
                'scope_type' => 'monthly',
                'sort_order' => 40,
                'cutoff_day' => 25,
            ],
            [
                'key' => 'spk.addendum',
                'feature_key' => 'spk',
                'action_key' => 'addendum',
                'label' => 'Addendum PK',
                'description' => 'Batas waktu pembuatan dan perubahan addendum Kontrak/Perjanjian Kerja.',
                'scope_type' => 'monthly',
                'sort_order' => 50,
                'cutoff_day' => 25,
            ],
            [
                'key' => 'bast.manage',
                'feature_key' => 'bast',
                'action_key' => 'manage',
                'label' => 'BAST',
                'description' => 'Batas waktu pembuatan dan pembaruan BAST serta lampiran.',
                'scope_type' => 'monthly',
                'sort_order' => 60,
                'cutoff_day' => 3,
            ],
        ];
    }

    public static function ensureDefaults(): void
    {
        if (! self::supportsStorage()) {
            return;
        }

        foreach (self::defaultDefinitions() as $definition) {
            $rule = static::query()->firstOrCreate(
                ['key' => $definition['key']],
                [
                    'feature_key' => $definition['feature_key'],
                    'action_key' => $definition['action_key'],
                    'label' => $definition['label'],
                    'description' => $definition['description'],
                    'scope_type' => $definition['scope_type'],
                    'sort_order' => $definition['sort_order'],
                    'cutoff_day' => $definition['cutoff_day'],
                    'is_enforced' => true,
                    'allow_manual_bypass' => true,
                ]
            );

            if (
                $rule->feature_key !== $definition['feature_key']
                || $rule->action_key !== $definition['action_key']
                || $rule->label !== $definition['label']
                || $rule->description !== $definition['description']
                || $rule->scope_type !== $definition['scope_type']
                || (int) $rule->sort_order !== (int) $definition['sort_order']
            ) {
                $rule->forceFill([
                    'feature_key' => $definition['feature_key'],
                    'action_key' => $definition['action_key'],
                    'label' => $definition['label'],
                    'description' => $definition['description'],
                    'scope_type' => $definition['scope_type'],
                    'sort_order' => $definition['sort_order'],
                ])->save();
            }

            if ($rule->cutoff_day === null || ! $rule->is_enforced || ! $rule->allow_manual_bypass) {
                $rule->forceFill([
                    'cutoff_day' => $definition['cutoff_day'],
                    'is_enforced' => true,
                    'allow_manual_bypass' => true,
                ])->save();
            }
        }
    }

    /**
     * @return Collection<int, self>
     */
    public static function ordered(): Collection
    {
        if (! self::supportsStorage()) {
            return collect();
        }

        self::ensureDefaults();

        return static::query()
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();
    }

    public static function supportsStorage(): bool
    {
        return Schema::hasTable('deadline_rules');
    }
}
