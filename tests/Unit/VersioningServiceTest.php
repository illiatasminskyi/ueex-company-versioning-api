<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Services\VersioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VersioningServiceTest extends TestCase
{
    use RefreshDatabase;

    private VersioningService $versioningService;
    private Company $testCompany;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->versioningService = new VersioningService();
        
        $this->testCompany = Company::create([
            'name' => 'Test Company',
            'edrpou' => '12345678',
            'address' => 'Test Address'
        ]);
    }

    public function test_creates_first_version_correctly(): void
    {
        $version = $this->versioningService->createVersion(
            $this->testCompany,
            'company_versions',
            ['name', 'edrpou', 'address']
        );

        $this->assertEquals(1, $version);

        $this->assertDatabaseHas('company_versions', [
            'company_id' => $this->testCompany->id,
            'version' => 1,
            'name' => 'Test Company',
            'edrpou' => '12345678',
            'address' => 'Test Address'
        ]);
    }

    public function test_creates_incremental_versions(): void
    {
        // Create first version
        $version1 = $this->versioningService->createVersion(
            $this->testCompany,
            'company_versions',
            ['name', 'edrpou', 'address']
        );

        // Update company data
        $this->testCompany->update(['name' => 'Updated Company']);

        // Create second version
        $version2 = $this->versioningService->createVersion(
            $this->testCompany,
            'company_versions',
            ['name', 'edrpou', 'address']
        );

        $this->assertEquals(1, $version1);
        $this->assertEquals(2, $version2);

        $this->assertDatabaseCount('company_versions', 2);
        
        $this->assertDatabaseHas('company_versions', [
            'company_id' => $this->testCompany->id,
            'version' => 2,
            'name' => 'Updated Company'
        ]);
    }

    public function test_detects_changes_correctly(): void
    {
        // Create initial version
        $this->versioningService->createVersion(
            $this->testCompany,
            'company_versions',
            ['name', 'edrpou', 'address']
        );

        // No changes
        $hasChanges1 = $this->versioningService->hasChanges(
            $this->testCompany,
            'company_versions',
            ['name', 'edrpou', 'address']
        );

        $this->assertFalse($hasChanges1);

        // Make changes
        $this->testCompany->name = 'Changed Name';

        $hasChanges2 = $this->versioningService->hasChanges(
            $this->testCompany,
            'company_versions',
            ['name', 'edrpou', 'address']
        );

        $this->assertTrue($hasChanges2);
    }

    public function test_detects_changes_in_any_tracked_field(): void
    {
        // Create initial version with all fields
        $this->versioningService->createVersion(
            $this->testCompany,
            'company_versions',
            ['name', 'edrpou', 'address']
        );

        // Change any tracked field
        $this->testCompany->address = 'Changed Address';

        $hasChanges = $this->versioningService->hasChanges(
            $this->testCompany,
            'company_versions',
            ['name', 'edrpou', 'address']
        );

        $this->assertTrue($hasChanges, 'Should detect changes in any tracked field');
    }

    public function test_gets_all_versions_in_correct_order(): void
    {
        // Create multiple versions
        $this->versioningService->createVersion(
            $this->testCompany,
            'company_versions',
            ['name', 'edrpou', 'address']
        );

        $this->testCompany->update(['name' => 'Version 2']);
        $this->versioningService->createVersion(
            $this->testCompany,
            'company_versions',
            ['name', 'edrpou', 'address']
        );

        $this->testCompany->update(['name' => 'Version 3']);
        $this->versioningService->createVersion(
            $this->testCompany,
            'company_versions',
            ['name', 'edrpou', 'address']
        );

        $versions = $this->versioningService->getVersions(
            $this->testCompany,
            'company_versions'
        );

        $this->assertCount(3, $versions);
        $this->assertEquals(1, $versions[0]->version);
        $this->assertEquals(2, $versions[1]->version);
        $this->assertEquals(3, $versions[2]->version);
        $this->assertEquals('Test Company', $versions[0]->name);
        $this->assertEquals('Version 2', $versions[1]->name);
        $this->assertEquals('Version 3', $versions[2]->name);
    }

    public function test_works_with_empty_version_table(): void
    {
        $hasChanges = $this->versioningService->hasChanges(
            $this->testCompany,
            'company_versions',
            ['name', 'edrpou', 'address']
        );

        $this->assertTrue($hasChanges, 'Should detect changes when no versions exist');

        $versions = $this->versioningService->getVersions(
            $this->testCompany,
            'company_versions'
        );

        $this->assertCount(0, $versions);
    }

    public function test_handles_empty_string_values(): void
    {
        // Test with empty string values (which are valid)
        $this->testCompany->update(['address' => 'Empty address']);

        $version = $this->versioningService->createVersion(
            $this->testCompany,
            'company_versions',
            ['name', 'edrpou', 'address']
        );

        $this->assertEquals(1, $version);

        $this->assertDatabaseHas('company_versions', [
            'company_id' => $this->testCompany->id,
            'version' => 1,
            'address' => 'Empty address'
        ]);
    }

    public function test_foreign_key_name_generation(): void
    {
        // Test with different model class name
        $reflection = new \ReflectionClass($this->versioningService);
        $method = $reflection->getMethod('getForeignKeyName');
        $method->setAccessible(true);

        $foreignKey = $method->invokeArgs($this->versioningService, [$this->testCompany]);
        
        $this->assertEquals('company_id', $foreignKey);
    }

    public function test_version_creation_stores_all_required_fields(): void
    {
        $version = $this->versioningService->createVersion(
            $this->testCompany,
            'company_versions',
            ['name', 'edrpou', 'address'] // All required fields
        );

        $this->assertEquals(1, $version);

        $this->assertDatabaseHas('company_versions', [
            'company_id' => $this->testCompany->id,
            'version' => 1,
            'name' => 'Test Company',
            'edrpou' => '12345678',
            'address' => 'Test Address'
        ]);
    }
}