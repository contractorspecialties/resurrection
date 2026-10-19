<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private function createCompany(string $name): Company
    {
        return Company::create([
            'name' => $name,
            'slug' => Company::uniqueSlug($name),
            'trade' => 'General Contractor',
            'city' => 'Charlotte',
            'state' => 'NC',
            'preferred_customer_contact' => 'email',
        ]);
    }

    private function createUser(Company $company, bool $admin): User
    {
        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $user->is_internal_admin = $admin;
        $user->save();

        return $user;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_contractor_cannot_access_admin(): void
    {
        $company = $this->createCompany('Test Contractor');
        $user = $this->createUser($company, false);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_contractor_cannot_view_another_company(): void
    {
        $first = $this->createCompany('First Company');
        $second = $this->createCompany('Second Company');

        $user = $this->createUser($first, false);

        $this->actingAs($user)
            ->get("/admin/companies/{$second->id}")
            ->assertForbidden();
    }

    public function test_administrator_can_view_admin_pages(): void
    {
        $company = $this->createCompany('Admin Test Company');
        $user = $this->createUser($company, true);

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk();

        $this->actingAs($user)
            ->get("/admin/companies/{$company->id}")
            ->assertOk();
    }
}
