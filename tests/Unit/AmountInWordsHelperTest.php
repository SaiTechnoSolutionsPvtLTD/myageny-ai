<?php

namespace Tests\Unit;

use Tests\TestCase;

class AmountInWordsHelperTest extends TestCase
{
    /**
     * Test amount_in_words helper with various amounts.
     */
    public function test_amount_in_words(): void
    {
        $this->assertEquals('Eighty Two Rupees and Eighty Two Paise Only', amount_in_words(82.82));
        $this->assertEquals('Eighty Two Rupees and Fifteen Paise Only', amount_in_words(82.15));
        $this->assertEquals('Eighty Two Rupees and Five Paise Only', amount_in_words(82.05));
        $this->assertEquals('Eighty Two Rupees Only', amount_in_words(82.00));
        $this->assertEquals('Eighty Two Rupees and Eighty Paise Only', amount_in_words(82.80));
        $this->assertEquals('One Hundred Rupees Only', amount_in_words(100));
        $this->assertEquals('One Lakh Two Thousand Three Hundred  and Forty Five Rupees and Sixty Seven Paise Only', amount_in_words(102345.67));
    }
}
