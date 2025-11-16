<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CompanyApiTest extends TestCase
{
    use RefreshDatabase;

    private array $validCompanyData = [
        'name' => 'ТОВ Тестова Компанія',
        'edrpou' => '12345678',
        'address' => 'м. Київ, вул. Тестова, 1'
    ];

    public function test_creates_new_company_successfully(): void
    {
        $response = $this->postJson('/api/company', $this->validCompanyData);

        $response->assertStatus(201)
                ->assertJson([
                    'status' => 'created',
                    'version' => 1
                ])
                ->assertJsonStructure([
                    'status',
                    'company_id',
                    'version'
                ]);

        $this->assertDatabaseHas('companies', [
            'name' => 'ТОВ Тестова Компанія',
            'edrpou' => '12345678',
            'address' => 'м. Київ, вул. Тестова, 1'
        ]);

        $this->assertDatabaseHas('company_versions', [
            'version' => 1,
            'name' => 'ТОВ Тестова Компанія',
            'edrpou' => '12345678',
            'address' => 'м. Київ, вул. Тестова, 1'
        ]);
    }

    public function test_updates_existing_company_when_data_changes(): void
    {
        // Create initial company
        $company = Company::create($this->validCompanyData);
        CompanyVersion::create([
            'company_id' => $company->id,
            'version' => 1,
            'name' => $this->validCompanyData['name'],
            'edrpou' => $this->validCompanyData['edrpou'],
            'address' => $this->validCompanyData['address']
        ]);

        // Update with new data
        $updatedData = [
            'name' => 'ТОВ Оновлена Компанія',
            'edrpou' => '12345678', // Same EDRPOU
            'address' => 'м. Київ, вул. Нова, 2'
        ];

        $response = $this->postJson('/api/company', $updatedData);

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'updated',
                    'company_id' => $company->id,
                    'version' => 2
                ]);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'ТОВ Оновлена Компанія',
            'address' => 'м. Київ, вул. Нова, 2'
        ]);

        $this->assertDatabaseHas('company_versions', [
            'company_id' => $company->id,
            'version' => 2,
            'name' => 'ТОВ Оновлена Компанія'
        ]);
    }

    public function test_returns_duplicate_when_no_changes(): void
    {
        // Create initial company
        $company = Company::create($this->validCompanyData);
        CompanyVersion::create([
            'company_id' => $company->id,
            'version' => 1,
            'name' => $this->validCompanyData['name'],
            'edrpou' => $this->validCompanyData['edrpou'],
            'address' => $this->validCompanyData['address']
        ]);

        // Send same data
        $response = $this->postJson('/api/company', $this->validCompanyData);

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'duplicate',
                    'company_id' => $company->id
                ])
                ->assertJsonMissingPath('version');

        // Check that no new version was created
        $this->assertDatabaseCount('company_versions', 1);
    }

    public function test_validates_required_fields(): void
    {
        $response = $this->postJson('/api/company', []);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'error',
                    'messages' => [
                        'name',
                        'edrpou',
                        'address'
                    ]
                ]);
    }

    public function test_validates_field_lengths(): void
    {
        $invalidData = [
            'name' => str_repeat('a', 257), // Too long
            'edrpou' => '12345678901', // Too long
            'address' => ''
        ];

        $response = $this->postJson('/api/company', $invalidData);

        $response->assertStatus(422)
                ->assertJsonPath('messages.name.0', 'The name field must not be greater than 256 characters.')
                ->assertJsonPath('messages.edrpou.0', 'The edrpou field must not be greater than 10 characters.');
    }

    public function test_gets_company_versions_by_edrpou(): void
    {
        // Create company with multiple versions
        $company = Company::create($this->validCompanyData);
        
        CompanyVersion::create([
            'company_id' => $company->id,
            'version' => 1,
            'name' => 'Original Name',
            'edrpou' => '12345678',
            'address' => 'Original Address'
        ]);

        CompanyVersion::create([
            'company_id' => $company->id,
            'version' => 2,
            'name' => 'Updated Name',
            'edrpou' => '12345678',
            'address' => 'Updated Address'
        ]);

        $response = $this->getJson('/api/company/12345678/versions');

        $response->assertStatus(200)
                ->assertJsonCount(2)
                ->assertJsonPath('0.version', 1)
                ->assertJsonPath('0.name', 'Original Name')
                ->assertJsonPath('1.version', 2)
                ->assertJsonPath('1.name', 'Updated Name')
                ->assertJsonStructure([
                    '*' => [
                        'version',
                        'name',
                        'edrpou',
                        'address',
                        'created_at'
                    ]
                ]);
    }

    public function test_returns_404_for_non_existent_company_versions(): void
    {
        $response = $this->getJson('/api/company/99999999/versions');

        $response->assertStatus(404)
                ->assertJson([
                    'error' => 'Company not found'
                ]);
    }

    public function test_handles_multiple_sequential_updates(): void
    {
        // Create initial company
        $this->postJson('/api/company', $this->validCompanyData);

        // First update
        $update1 = array_merge($this->validCompanyData, ['name' => 'Updated Name 1']);
        $response1 = $this->postJson('/api/company', $update1);
        $response1->assertJson(['status' => 'updated', 'version' => 2]);

        // Second update
        $update2 = array_merge($this->validCompanyData, ['address' => 'Updated Address']);
        $response2 = $this->postJson('/api/company', $update2);
        $response2->assertJson(['status' => 'updated', 'version' => 3]);

        // Verify version count
        $this->assertDatabaseCount('company_versions', 3);
    }

    public function test_handles_concurrent_edrpou_uniqueness(): void
    {
        $company1Data = $this->validCompanyData;
        $company2Data = [
            'name' => 'Інша Компанія',
            'edrpou' => '87654321',
            'address' => 'Інша адреса'
        ];

        $response1 = $this->postJson('/api/company', $company1Data);
        $response2 = $this->postJson('/api/company', $company2Data);

        $response1->assertStatus(201);
        $response2->assertStatus(201);

        $this->assertDatabaseCount('companies', 2);
        $this->assertDatabaseCount('company_versions', 2);
    }
}