# Database Seeders - Undian Kendaraan

## Overview

Database seeders sudah dikonfigurasi untuk mengimport data dari SQL dump yang Anda berikan. Seeders ini akan otomatis membuat data awal untuk sistem undian kendaraan.

## Struktur Seeders

### 📋 Urutan Eksekusi (Automatic via DatabaseSeeder)
1. **UserSeeder** - Setup user admin
2. **PrizeCategorySeeder** - Setup kategori hadiah (3 kategori)
3. **PrizeSeeder** - Setup hadiah/prizes (6 sampel hadiah)
4. **KendaraanSeeder** - Setup 290+ kendaraan dari JSON file
5. **DrawSeeder** - Setup sample draw records

### 📁 File Locations
- `/database/seeders/DatabaseSeeder.php` - Main seeder controller
- `/database/seeders/UserSeeder.php` - User data
- `/database/seeders/PrizeCategorySeeder.php` - Prize categories
- `/database/seeders/PrizeSeeder.php` - Prize items
- `/database/seeders/KendaraanSeeder.php` - Vehicle data (loads from JSON)
- `/database/seeders/DrawSeeder.php` - Draw records
- `/database/seeders/jsons/kendaraans.json` - 290+ vehicle records

## Quick Start

### Option 1: Fresh Install with Seeding
```bash
# Run all migrations + all seeders
php artisan migrate:fresh --seed
```

### Option 2: Seed Only (existing database)
```bash
# Run all seeders defined in DatabaseSeeder
php artisan db:seed
```

### Option 3: Run Specific Seeder
```bash
php artisan db:seed --class=KendaraanSeeder
php artisan db:seed --class=PrizeSeeder
php artisan db:seed --class=DrawSeeder
```

## Data Included

### Kendaraan Data
- **Total Records**: 290+ vehicles
- **Source**: `database/seeders/jsons/kendaraans.json`
- **Coverage**: 30 locations across Central Java
- **Fields**: License plate, owner name, address, location, wheels, dates, etc.
- **Date Range**: 2025-2026

### Prize Categories (3 total)
1. **Grand Prize** (Level 1) - Main prizes
2. **Hadiah Utama** (Level 2) - Main category prizes
3. **Hadiah Hiburan** (Level 3) - Entertainment prizes

### Sample Prizes (6 total)
- Sepeda Motor 150cc (5x) - Grand Prize
- Sepeda Motor 250cc (3x) - Grand Prize
- Smart TV 43 Inch (10x) - Main Prize
- Laptop Gaming (7x) - Main Prize
- Voucher Belanja 500rb (100x) - Entertainment Prize
- Speaker Bluetooth (50x) - Entertainment Prize

### Sample User
- **Email**: superadmin@example.com
- **Password**: 12345678
- **Name**: lookmains

## Important Notes

1. **ID Kendaraan**: Uses `id_kendaraan` field for uniqueness check to prevent duplicates
2. **updateOrCreate**: Seeders use `updateOrCreate` to handle re-seeding safely
3. **Location Mapping**: All 30 Central Java locations properly mapped with location codes
4. **Dates Format**: Dates stored as YYYY-MM-DD format
5. **JSON File**: Complete vehicle list is stored in JSON for easy management

## Troubleshooting

### Error: "Class KendaraanSeeder not found"
- Ensure `php artisan composer dump-autoload` was run
- Check file permissions on seeder files

### Error: "Column not found"
- Verify all migrations have been run: `php artisan migrate:status`
- Run `php artisan migrate` first, then `php artisan db:seed`

### Duplicate Entry Errors
- This is normal if seeding multiple times
- Seeders use `updateOrCreate` to handle this gracefully
- For fresh start: `php artisan migrate:fresh --seed`

## Customization

### To Add More Vehicles
Edit `/database/seeders/jsons/kendaraans.json` and add more records:
```json
{
    "id_kendaraan": "10000291",
    "no_polisi": "H1234AB",
    "nama": "NAMA PEMILIK",
    "alamat": "JL. EXAMPLE NO.1",
    ...
}
```

### To Add More Prizes
Edit `/database/seeders/PrizeSeeder.php`:
```php
[
    'prize_category_id' => 2,
    'name' => 'Prize Name',
    'description' => 'Description',
    'available_quantity' => 10,
    'quantity' => 10,
]
```

### To Add More Categories
Edit `/database/seeders/PrizeCategorySeeder.php`:
```php
[
    'name' => 'New Category',
    'level' => 4,
    'description' => 'Description',
]
```

## Verification

After seeding, verify data in database:

```bash
# Check user count
php artisan tinker
>>> App\Models\User::count()

# Check vehicles
>>> App\Models\Kendaraan::count()

# Check prizes
>>> App\Models\Prize::count()

# Check prize categories
>>> App\Models\PrizeCategory::count()

# Check draws
>>> App\Models\Draw::count()
```

Expected counts:
- Users: 1
- Kendaraan: 290+
- Prize Categories: 3
- Prizes: 6 (sample data)
- Draws: 6 (sample data)

---
**Last Updated**: 2026-06-17  
**Database Version**: Laravel 10.x with MySQL
