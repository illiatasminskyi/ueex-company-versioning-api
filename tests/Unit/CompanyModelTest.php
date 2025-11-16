<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\CompanyVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_model_has_correct_fillable_fields(): void
    {
        $company = new Company();
        
        $expectedFillable = ['name', 'edrpou', 'address'];
        
        $this->assertEquals($expectedFillable, $company->getFillable());
    }

    public function test_company_has_versions_relationship(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
            'edrpou' => '12345678',
            'address' => 'Test Address'
        ]);

        CompanyVersion::create([
            'company_id' => $company->id,
            'version' => 1,
            'name' => 'Test Company',
            'edrpou' => '12345678',
            'address' => 'Test Address'
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $company->versions);
        $this->assertCount(1, $company->versions);
        $this->assertInstanceOf(CompanyVersion::class, $company->versions->first());
    }

    public function test_company_version_has_correct_fillable_fields(): void
    {
        $companyVersion = new CompanyVersion();
        
        $expectedFillable = ['company_id', 'version', 'name', 'edrpou', 'address', 'created_at'];
        
        $this->assertEquals($expectedFillable, $companyVersion->getFillable());
    }

    public function test_company_version_belongs_to_company(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
            'edrpou' => '12345678',
            'address' => 'Test Address'
        ]);

        $companyVersion = CompanyVersion::create([
            'company_id' => $company->id,
            'version' => 1,
            'name' => 'Test Company',
            'edrpou' => '12345678',
            'address' => 'Test Address'
        ]);

        $this->assertInstanceOf(Company::class, $companyVersion->company);
        $this->assertEquals($company->id, $companyVersion->company->id);
    }

    public function test_edrpou_uniqueness_constraint(): void
    {
        Company::create([
            'name' => 'First Company',
            'edrpou' => '12345678',
            'address' => 'First Address'
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Company::create([
            'name' => 'Second Company',
            'edrpou' => '12345678', // Duplicate EDRPOU
            'address' => 'Second Address'
        ]);
    }

    public function test_company_creation_with_valid_data(): void
    {
        $companyData = [
            'name' => 'Тестова Компанія',
            'edrpou' => '87654321',
            'address' => 'м. Львів, вул. Шевченка, 1'
        ];

        $company = Company::create($companyData);

        $this->assertInstanceOf(Company::class, $company);
        $this->assertEquals($companyData['name'], $company->name);
        $this->assertEquals($companyData['edrpou'], $company->edrpou);
        $this->assertEquals($companyData['address'], $company->address);
        $this->assertNotNull($company->created_at);
        $this->assertNotNull($company->updated_at);
    }

    public function test_company_version_creation_with_valid_data(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
            'edrpou' => '12345678',
            'address' => 'Test Address'
        ]);

        $versionData = [
            'company_id' => $company->id,
            'version' => 1,
            'name' => 'Test Company V1',
            'edrpou' => '12345678',
            'address' => 'Test Address V1'
        ];

        $companyVersion = CompanyVersion::create($versionData);

        $this->assertInstanceOf(CompanyVersion::class, $companyVersion);
        $this->assertEquals($versionData['company_id'], $companyVersion->company_id);
        $this->assertEquals($versionData['version'], $companyVersion->version);
        $this->assertEquals($versionData['name'], $companyVersion->name);
        $this->assertNotNull($companyVersion->created_at);
    }

    public function test_company_cascade_delete(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
            'edrpou' => '12345678',
            'address' => 'Test Address'
        ]);

        CompanyVersion::create([
            'company_id' => $company->id,
            'version' => 1,
            'name' => 'Test Company',
            'edrpou' => '12345678',
            'address' => 'Test Address'
        ]);

        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('company_versions', 1);

        $company->delete();

        $this->assertDatabaseCount('companies', 0);
        $this->assertDatabaseCount('company_versions', 0);
    }

    public function test_company_timestamps_behavior(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
            'edrpou' => '12345678',
            'address' => 'Test Address'
        ]);

        $originalUpdatedAt = $company->updated_at;

        // Wait a moment and update
        sleep(1);
        $company->update(['name' => 'Updated Company']);

        $this->assertNotEquals($originalUpdatedAt, $company->fresh()->updated_at);
    }

    public function test_company_version_timestamps_behavior(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
            'edrpou' => '12345678',
            'address' => 'Test Address'
        ]);

        $companyVersion = CompanyVersion::create([
            'company_id' => $company->id,
            'version' => 1,
            'name' => 'Test Company',
            'edrpou' => '12345678',
            'address' => 'Test Address'
        ]);

        // CompanyVersion should only have created_at, not updated_at
        $this->assertNotNull($companyVersion->created_at);
        $this->assertFalse($companyVersion->timestamps);
    }
}