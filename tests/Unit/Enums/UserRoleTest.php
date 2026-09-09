<?php

namespace Tests\Unit\Enums;

use App\Enums\UserRole;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    #[DataProvider('roleProvider')]
    public function test_role_helpers(UserRole $role, bool $isFreelancer, bool $isClient): void
    {
        $this->assertSame($isFreelancer, $role->isFreelancer());
        $this->assertSame($isClient, $role->isClient());
    }

    public static function roleProvider(): array
    {
        return [
            'freelancer' => [UserRole::Freelancer, true, false],
            'client' => [UserRole::Client, false, true],
        ];
    }

    public function test_values_returns_all_role_strings(): void
    {
        $this->assertSame(
            ['freelancer', 'client'],
            UserRole::values(),
        );
    }
}
