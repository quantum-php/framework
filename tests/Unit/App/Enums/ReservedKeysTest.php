<?php

namespace Quantum\Tests\Unit\App\Enums;

use Quantum\App\Enums\ReservedKeys;
use PHPUnit\Framework\TestCase;
use BackedEnum;

class ReservedKeysTest extends TestCase
{
    public function testReservedKeysIsAStringBackedEnum(): void
    {
        $this->assertTrue(enum_exists(ReservedKeys::class));
        $this->assertTrue(is_subclass_of(ReservedKeys::class, BackedEnum::class));
    }

    public function testCasesKeepTheirValues(): void
    {
        $this->assertSame('_qt_rendered_view', ReservedKeys::RENDERED_VIEW->value);
        $this->assertSame('_qt_prev_request', ReservedKeys::PREV_REQUEST->value);
    }

    public function testThereAreNoOtherCases(): void
    {
        $this->assertSame(
            [ReservedKeys::RENDERED_VIEW, ReservedKeys::PREV_REQUEST],
            ReservedKeys::cases()
        );
    }
}
