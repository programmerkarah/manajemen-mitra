<?php

namespace Tests\Feature;

use Tests\TestCase;

class SpkBatchAddendumRouteTest extends TestCase
{
    public function test_generate_batch_addendum_returns_404_when_hashid_is_invalid(): void
    {
        $this->withoutMiddleware();

        $this->post('/spk/periode/invalid-hash/generate-addendum-batch', [
            'tanggal_spk' => now()->toDateString(),
            'sampai_tanggal' => now()->addDay()->toDateString(),
            'petugas_ids' => ['invalid-petugas-hash'],
        ])->assertNotFound();
    }
}
