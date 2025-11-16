<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyVersion;
use Database\Seeders\CompanySeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_seeder_creates_expected_data(): void
    {
        $seeder = new CompanySeeder();
        $seeder->run();

        // Should create 3 companies
        $this->assertDatabaseCount('companies', 3);

        // Should create 6 versions (2 per company)
        $this->assertDatabaseCount('company_versions', 6);

        // Check specific companies
        $this->assertDatabaseHas('companies', [
            'name' => 'ТОВ Українська Енергетична Біржа',
            'edrpou' => '37027819'
        ]);

        $this->assertDatabaseHas('companies', [
            'name' => 'ТОВ Ромашка Плюс',
            'edrpou' => '12345678'
        ]);

        $this->assertDatabaseHas('companies', [
            'name' => 'ТОВ ТехноСервіс',
            'edrpou' => '87654321'
        ]);
    }

    public function test_company_seeder_creates_versions_correctly(): void
    {
        $seeder = new CompanySeeder();
        $seeder->run();

        $company = Company::where('edrpou', '37027819')->first();
        $this->assertNotNull($company);

        $versions = CompanyVersion::where('company_id', $company->id)
                                 ->orderBy('version')
                                 ->get();

        $this->assertCount(2, $versions);

        // Check version 1
        $this->assertEquals(1, $versions[0]->version);
        $this->assertEquals('ТОВ Українська Енергетична Біржа', $versions[0]->name);
        $this->assertEquals('м. Київ, вул. Хрещатик, 22', $versions[0]->address);

        // Check version 2
        $this->assertEquals(2, $versions[1]->version);
        $this->assertEquals('ТОВ Українська Енергетична Біржа', $versions[1]->name);
        $this->assertEquals('м. Київ, вул. Хрещатик, 22А, офіс 501', $versions[1]->address);
    }

    public function test_company_seeder_creates_address_changes(): void
    {
        $seeder = new CompanySeeder();
        $seeder->run();

        // Check ТОВ ТехноСервіс address change
        $company = Company::where('edrpou', '87654321')->first();
        $versions = CompanyVersion::where('company_id', $company->id)
                                 ->orderBy('version')
                                 ->get();

        $this->assertEquals('м. Харків, проспект Науки, 10', $versions[0]->address);
        $this->assertEquals('м. Харків, проспект Науки, 12, корпус Б', $versions[1]->address);
    }

    public function test_company_seeder_creates_name_changes(): void
    {
        $seeder = new CompanySeeder();
        $seeder->run();

        // Check ТОВ Ромашка name change
        $company = Company::where('edrpou', '12345678')->first();
        $versions = CompanyVersion::where('company_id', $company->id)
                                 ->orderBy('version')
                                 ->get();

        $this->assertEquals('ТОВ Ромашка', $versions[0]->name);
        $this->assertEquals('ТОВ Ромашка Плюс', $versions[1]->name);
    }

    public function test_database_seeder_runs_without_errors(): void
    {
        $seeder = new DatabaseSeeder();
        $seeder->run();

        // Should have user from UserFactory
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com'
        ]);

        // Should have companies from CompanySeeder
        $this->assertDatabaseCount('companies', 3);
        $this->assertDatabaseCount('company_versions', 6);
    }

    public function test_seeder_handles_multiple_runs(): void
    {
        $seeder = new CompanySeeder();
        
        // Run seeder first time
        $seeder->run();
        $this->assertDatabaseCount('companies', 3);

        // Run seeder second time (should not duplicate due to unique EDRPOU)
        try {
            $seeder->run();
            $this->fail('Expected exception due to unique constraint violation');
        } catch (\Exception $e) {
            // This is expected due to unique EDRPOU constraint
            $this->assertStringContainsString('UNIQUE constraint failed', $e->getMessage());
        }
    }

    public function test_seeder_data_integrity(): void
    {
        $seeder = new CompanySeeder();
        $seeder->run();

        // Check that all companies have corresponding versions
        $companies = Company::all();
        
        foreach ($companies as $company) {
            $versionsCount = CompanyVersion::where('company_id', $company->id)->count();
            $this->assertEquals(2, $versionsCount, "Company {$company->name} should have exactly 2 versions");
        }

        // Check that final company data matches latest version
        foreach ($companies as $company) {
            $latestVersion = CompanyVersion::where('company_id', $company->id)
                                         ->orderBy('version', 'desc')
                                         ->first();

            $this->assertEquals($company->name, $latestVersion->name);
            $this->assertEquals($company->edrpou, $latestVersion->edrpou);
            $this->assertEquals($company->address, $latestVersion->address);
        }
    }

    public function test_seeder_version_timestamps(): void
    {
        $seeder = new CompanySeeder();
        $seeder->run();

        $versions = CompanyVersion::all();

        foreach ($versions as $version) {
            $this->assertNotNull($version->created_at);
            $this->assertInstanceOf(\Carbon\Carbon::class, $version->created_at);
        }
    }

    public function test_seeder_edrpou_uniqueness(): void
    {
        $seeder = new CompanySeeder();
        $seeder->run();

        $edrpous = Company::pluck('edrpou')->toArray();
        $uniqueEdrpous = array_unique($edrpous);

        $this->assertCount(3, $edrpous);
        $this->assertCount(3, $uniqueEdrpous);
        $this->assertEquals($edrpous, $uniqueEdrpous);
    }
}