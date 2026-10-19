<?php

namespace Tests\Unit\Support;

use App\Support\SalonTitle;
use PHPUnit\Framework\TestCase;

class SalonTitleTest extends TestCase
{
    public function test_plain_name_gets_the_prefix(): void
    {
        $this->assertSame('سالن زیبایی راستا', SalonTitle::for('راستا'));
        $this->assertSame('سالن زیبایی ماهرو', SalonTitle::for(' ماهرو '));
    }

    public function test_name_that_already_names_the_business_is_left_alone(): void
    {
        $this->assertSame('سالن زیبایی باران', SalonTitle::for('سالن زیبایی باران'));
        $this->assertSame('آرایشگاه یاس', SalonTitle::for('آرایشگاه یاس'));
        $this->assertSame('كلينيك پوست', SalonTitle::for('كلينيك پوست'));
    }
}
