<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\CompanyVersion;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create companies with their versions
        $companies = [
            [
                'company' => [
                    'name' => 'ТОВ Українська Енергетична Біржа',
                    'edrpou' => '37027819',
                    'address' => 'м. Київ, вул. Хрещатик, 22А, офіс 501',
                ],
                'versions' => [
                    [
                        'version' => 1,
                        'name' => 'ТОВ Українська Енергетична Біржа',
                        'edrpou' => '37027819',
                        'address' => 'м. Київ, вул. Хрещатик, 22',
                    ],
                    [
                        'version' => 2,
                        'name' => 'ТОВ Українська Енергетична Біржа',
                        'edrpou' => '37027819',
                        'address' => 'м. Київ, вул. Хрещатик, 22А, офіс 501',
                    ],
                ]
            ],
            [
                'company' => [
                    'name' => 'ТОВ Ромашка Плюс',
                    'edrpou' => '12345678',
                    'address' => 'м. Львів, вул. Городоцька, 15',
                ],
                'versions' => [
                    [
                        'version' => 1,
                        'name' => 'ТОВ Ромашка',
                        'edrpou' => '12345678',
                        'address' => 'м. Львів, вул. Городоцька, 15',
                    ],
                    [
                        'version' => 2,
                        'name' => 'ТОВ Ромашка Плюс',
                        'edrpou' => '12345678',
                        'address' => 'м. Львів, вул. Городоцька, 15',
                    ],
                ]
            ],
            [
                'company' => [
                    'name' => 'ТОВ ТехноСервіс',
                    'edrpou' => '87654321',
                    'address' => 'м. Харків, проспект Науки, 12, корпус Б',
                ],
                'versions' => [
                    [
                        'version' => 1,
                        'name' => 'ТОВ ТехноСервіс',
                        'edrpou' => '87654321',
                        'address' => 'м. Харків, проспект Науки, 10',
                    ],
                    [
                        'version' => 2,
                        'name' => 'ТОВ ТехноСервіс',
                        'edrpou' => '87654321',
                        'address' => 'м. Харків, проспект Науки, 12, корпус Б',
                    ],
                ]
            ],
        ];

        foreach ($companies as $companyData) {
            // Create company
            $company = Company::create($companyData['company']);

            // Create versions for this company
            foreach ($companyData['versions'] as $versionData) {
                CompanyVersion::create([
                    'company_id' => $company->id,
                    'version' => $versionData['version'],
                    'name' => $versionData['name'],
                    'edrpou' => $versionData['edrpou'],
                    'address' => $versionData['address'],
                ]);
            }
        }
    }
}
