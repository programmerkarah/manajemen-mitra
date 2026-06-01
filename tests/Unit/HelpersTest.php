<?php

namespace Tests\Unit;

use Tests\TestCase;

class HelpersTest extends TestCase
{
    public function test_terbilang_uses_correct_indonesian_words_for_11_to_19(): void
    {
        $this->assertSame('sebelas ', terbilang(11));
        $this->assertSame('dua belas ', terbilang(12));
        $this->assertSame('tiga belas ', terbilang(13));
        $this->assertSame('empat belas ', terbilang(14));
        $this->assertSame('lima belas ', terbilang(15));
        $this->assertSame('enam belas ', terbilang(16));
        $this->assertSame('tujuh belas ', terbilang(17));
        $this->assertSame('delapan belas ', terbilang(18));
        $this->assertSame('sembilan belas ', terbilang(19));
    }

    public function test_terbilang_formats_million_values_without_float_artifacts(): void
    {
        $this->assertSame(
            'sebelas juta sembilan ratus lima puluh ribu ',
            terbilang(11950000),
        );
    }
}
