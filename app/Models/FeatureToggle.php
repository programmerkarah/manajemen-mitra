<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class FeatureToggle extends Model
{
    protected $table = 'feature_toggles';

    protected $fillable = [
        'key',
        'label',
        'description',
        'enabled',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return array<int, array{key:string,label:string,description:string,sort_order:int}>
     */
    public static function defaultDefinitions(): array
    {
        return [
            ['key' => 'kegiatan', 'label' => 'Kegiatan', 'description' => 'Menjalankan seluruh fitur pengelolaan kegiatan.', 'sort_order' => 10],
            ['key' => 'alokasi', 'label' => 'Alokasi', 'description' => 'Menjalankan fitur alokasi petugas dan periode alokasi.', 'sort_order' => 20],
            ['key' => 'spk', 'label' => 'SPK', 'description' => 'Menjalankan fitur surat perjanjian kerja.', 'sort_order' => 30],
            ['key' => 'bast', 'label' => 'BAST', 'description' => 'Menjalankan fitur berita acara serah terima.', 'sort_order' => 40],
            ['key' => 'pengajuan_pulsa', 'label' => 'Pengajuan Pulsa', 'description' => 'Menjalankan fitur pengajuan pulsa.', 'sort_order' => 50],
            ['key' => 'petugas', 'label' => 'Petugas', 'description' => 'Menjalankan fitur manajemen petugas.', 'sort_order' => 60],
        ];
    }

    public static function ensureDefaults(): void
    {
        foreach (self::defaultDefinitions() as $definition) {
            static::query()->firstOrCreate(
                ['key' => $definition['key']],
                [
                    'label' => $definition['label'],
                    'description' => $definition['description'],
                    'sort_order' => $definition['sort_order'],
                    'enabled' => true,
                ]
            );
        }
    }

    /**
     * @return Collection<int, self>
     */
    public static function ordered(): Collection
    {
        self::ensureDefaults();

        return static::query()
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();
    }

    public static function isEnabled(string $key): bool
    {
        self::ensureDefaults();

        $enabled = static::query()->where('key', $key)->value('enabled');

        return $enabled === null ? true : (bool) $enabled;
    }

    public static function updateState(string $key, bool $enabled): self
    {
        self::ensureDefaults();

        /** @var self $toggle */
        $toggle = static::query()->updateOrCreate(
            ['key' => $key],
            ['enabled' => $enabled]
        );

        return $toggle;
    }

    public static function featureKeyForRouteName(?string $routeName): ?string
    {
        if (! is_string($routeName) || trim($routeName) === '') {
            return null;
        }

        $routeKey = strtolower(trim($routeName));

        $routeMap = [
            'kegiatan' => ['kegiatan.'],
            'alokasi' => ['alokasi.'],
            'spk' => ['spk.'],
            'bast' => ['bast.'],
            'pengajuan_pulsa' => ['pengajuan-pulsa.'],
            'petugas' => ['petugas.'],
        ];

        foreach ($routeMap as $featureKey => $prefixes) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($routeKey, $prefix)) {
                    return $featureKey;
                }
            }
        }

        return null;
    }

    public static function isRouteEnabled(?string $routeName): bool
    {
        $featureKey = self::featureKeyForRouteName($routeName);

        if ($featureKey === null) {
            return true;
        }

        return self::isEnabled($featureKey);
    }
}
