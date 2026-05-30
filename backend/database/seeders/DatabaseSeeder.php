<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Medicine;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Users
        User::create([
            'name'     => 'Admin User',
            'email'    => 'admin@pharmacy.com',
            'password' => Hash::make('password'),
            'role'     => 'admin',
        ]);

        User::create([
            'name'     => 'Pharmacist',
            'email'    => 'pharmacist@pharmacy.com',
            'password' => Hash::make('password'),
            'role'     => 'pharmacist',
        ]);

        // Categories
        $categories = [
            ['name' => 'Antibiotics',       'description' => 'Bacterial infection treatments'],
            ['name' => 'Analgesics',         'description' => 'Pain relief medicines'],
            ['name' => 'Antihypertensives',  'description' => 'Blood pressure medicines'],
            ['name' => 'Antidiabetics',      'description' => 'Diabetes management'],
            ['name' => 'Vitamins & Supplements', 'description' => 'Nutritional supplements'],
            ['name' => 'Antihistamines',     'description' => 'Allergy medicines'],
            ['name' => 'Antacids',           'description' => 'Digestive and stomach medicines'],
            ['name' => 'Antimalarials',      'description' => 'Malaria prevention and treatment'],
        ];

        foreach ($categories as $cat) {
            Category::create($cat);
        }

        // Suppliers
        $suppliers = [
            ['name' => 'MedSupply Ethiopia', 'contact_person' => 'Abebe Girma',   'phone' => '+251911234567', 'email' => 'info@medsupply.et',   'address' => 'Addis Ababa, Ethiopia'],
            ['name' => 'PharmaLink Ltd',     'contact_person' => 'Sara Johnson',  'phone' => '+251922345678', 'email' => 'sales@pharmalink.com', 'address' => 'Addis Ababa, Ethiopia'],
            ['name' => 'HealthSource Co',    'contact_person' => 'Dawit Bekele',  'phone' => '+251933456789', 'email' => 'orders@healthsource.et','address' => 'Dire Dawa, Ethiopia'],
        ];

        foreach ($suppliers as $sup) {
            Supplier::create($sup);
        }

        // Medicines
        $medicines = [
            ['name' => 'Amoxicillin 500mg',     'generic_name' => 'Amoxicillin',     'category_id' => 1, 'supplier_id' => 1, 'dosage_form' => 'Capsule',  'strength' => '500mg',    'unit' => 'Pcs', 'purchase_price' => 5.00,  'selling_price' => 8.00,  'stock_quantity' => 200, 'reorder_level' => 50,  'expiry_date' => '2026-12-31', 'requires_prescription' => true],
            ['name' => 'Paracetamol 500mg',      'generic_name' => 'Paracetamol',     'category_id' => 2, 'supplier_id' => 1, 'dosage_form' => 'Tablet',   'strength' => '500mg',    'unit' => 'Pcs', 'purchase_price' => 1.00,  'selling_price' => 2.50,  'stock_quantity' => 500, 'reorder_level' => 100, 'expiry_date' => '2027-06-30', 'requires_prescription' => false],
            ['name' => 'Metformin 500mg',        'generic_name' => 'Metformin',       'category_id' => 4, 'supplier_id' => 2, 'dosage_form' => 'Tablet',   'strength' => '500mg',    'unit' => 'Pcs', 'purchase_price' => 3.50,  'selling_price' => 6.00,  'stock_quantity' => 8,   'reorder_level' => 30,  'expiry_date' => '2026-08-31', 'requires_prescription' => true],
            ['name' => 'Amlodipine 5mg',         'generic_name' => 'Amlodipine',      'category_id' => 3, 'supplier_id' => 2, 'dosage_form' => 'Tablet',   'strength' => '5mg',      'unit' => 'Pcs', 'purchase_price' => 4.00,  'selling_price' => 7.50,  'stock_quantity' => 0,   'reorder_level' => 40,  'expiry_date' => '2026-10-31', 'requires_prescription' => true],
            ['name' => 'Vitamin C 500mg',        'generic_name' => 'Ascorbic Acid',   'category_id' => 5, 'supplier_id' => 3, 'dosage_form' => 'Tablet',   'strength' => '500mg',    'unit' => 'Pcs', 'purchase_price' => 2.00,  'selling_price' => 4.00,  'stock_quantity' => 300, 'reorder_level' => 60,  'expiry_date' => '2027-03-31', 'requires_prescription' => false],
            ['name' => 'Cetirizine 10mg',        'generic_name' => 'Cetirizine',      'category_id' => 6, 'supplier_id' => 1, 'dosage_form' => 'Tablet',   'strength' => '10mg',     'unit' => 'Pcs', 'purchase_price' => 2.50,  'selling_price' => 5.00,  'stock_quantity' => 150, 'reorder_level' => 30,  'expiry_date' => '2026-09-30', 'requires_prescription' => false],
            ['name' => 'Omeprazole 20mg',        'generic_name' => 'Omeprazole',      'category_id' => 7, 'supplier_id' => 2, 'dosage_form' => 'Capsule',  'strength' => '20mg',     'unit' => 'Pcs', 'purchase_price' => 3.00,  'selling_price' => 6.50,  'stock_quantity' => 12,  'reorder_level' => 40,  'expiry_date' => '2025-12-31', 'requires_prescription' => false],
            ['name' => 'Artemether/Lumefantrine', 'generic_name' => 'Coartem',        'category_id' => 8, 'supplier_id' => 3, 'dosage_form' => 'Tablet',   'strength' => '20/120mg', 'unit' => 'Pack','purchase_price' => 15.00, 'selling_price' => 25.00, 'stock_quantity' => 80,  'reorder_level' => 20,  'expiry_date' => '2027-01-31', 'requires_prescription' => true],
            ['name' => 'Ibuprofen 400mg',        'generic_name' => 'Ibuprofen',       'category_id' => 2, 'supplier_id' => 1, 'dosage_form' => 'Tablet',   'strength' => '400mg',    'unit' => 'Pcs', 'purchase_price' => 1.50,  'selling_price' => 3.50,  'stock_quantity' => 250, 'reorder_level' => 50,  'expiry_date' => '2027-04-30', 'requires_prescription' => false],
            ['name' => 'ORS Sachets',            'generic_name' => 'Oral Rehydration','category_id' => 7, 'supplier_id' => 3, 'dosage_form' => 'Sachet',   'strength' => '1L',       'unit' => 'Pcs', 'purchase_price' => 0.50,  'selling_price' => 1.50,  'stock_quantity' => 400, 'reorder_level' => 80,  'expiry_date' => '2027-12-31', 'requires_prescription' => false],
        ];

        foreach ($medicines as $med) {
            $med['sku'] = 'MED-' . strtoupper(Str::random(8));
            Medicine::create($med);
        }
    }
}
