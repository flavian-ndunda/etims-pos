<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create demo users with different roles
        User::firstOrCreate(['email' => 'admin@demo.co.ke'], [
            'name'     => 'Admin User',
            'password' => Hash::make('password'),
            'role'     => 'admin',
        ]);

        User::firstOrCreate(['email' => 'manager@demo.co.ke'], [
            'name'     => 'Store Manager',
            'password' => Hash::make('password'),
            'role'     => 'manager',
        ]);

        User::firstOrCreate(['email' => 'cashier@demo.co.ke'], [
            'name'     => 'Jane Wanjiru',
            'password' => Hash::make('password'),
            'role'     => 'cashier',
        ]);

        // Categories
        $cats = [
            ['name' => 'Beverages'],
            ['name' => 'Food & Groceries'],
            ['name' => 'Dairy & Eggs'],
            ['name' => 'Personal Care'],
            ['name' => 'Alcohol'],
        ];
        foreach ($cats as $c) Category::firstOrCreate(['name' => $c['name']], $c);

        $bev   = Category::where('name', 'Beverages')->first();
        $food  = Category::where('name', 'Food & Groceries')->first();
        $dairy = Category::where('name', 'Dairy & Eggs')->first();
        $care  = Category::where('name', 'Personal Care')->first();
        $alc   = Category::where('name', 'Alcohol')->first();

        $products = [
            ['name'=>'Coca-Cola 500ml',   'sku'=>'BEV-COKE','price'=>65.00, 'tax_type_code'=>'A','item_category'=>'50202301','category_id'=>$bev->id,  'stock_quantity'=>200,'buying_price'=>48.00],
            ['name'=>'Water 500ml',        'sku'=>'BEV-WAT', 'price'=>40.00, 'tax_type_code'=>'B','item_category'=>'50202304','category_id'=>$bev->id,  'stock_quantity'=>300,'buying_price'=>25.00],
            ['name'=>'Juice Mango 1L',     'sku'=>'BEV-JUC', 'price'=>150.00,'tax_type_code'=>'A','item_category'=>'50202302','category_id'=>$bev->id,  'stock_quantity'=>80, 'buying_price'=>110.00],
            ['name'=>'Unga Pembe 2kg',     'sku'=>'FD-UNGA', 'price'=>190.00,'tax_type_code'=>'B','item_category'=>'50131500','category_id'=>$food->id, 'stock_quantity'=>100,'buying_price'=>155.00],
            ['name'=>'Rice Pishori 1kg',   'sku'=>'FD-RICE', 'price'=>145.00,'tax_type_code'=>'B','item_category'=>'50131501','category_id'=>$food->id, 'stock_quantity'=>120,'buying_price'=>115.00],
            ['name'=>'Sugar Mumias 1kg',   'sku'=>'FD-SUG',  'price'=>165.00,'tax_type_code'=>'B','item_category'=>'50221000','category_id'=>$food->id, 'stock_quantity'=>150,'buying_price'=>130.00],
            ['name'=>'Bread White 400g',   'sku'=>'FD-BRD',  'price'=>55.00, 'tax_type_code'=>'B','item_category'=>'50131600','category_id'=>$food->id, 'stock_quantity'=>60, 'buying_price'=>42.00],
            ['name'=>'Cooking Oil 1L',     'sku'=>'FD-OIL',  'price'=>220.00,'tax_type_code'=>'A','item_category'=>'50221500','category_id'=>$food->id, 'stock_quantity'=>80, 'buying_price'=>175.00],
            ['name'=>'Milk Fresh 1L',      'sku'=>'DRY-MLK', 'price'=>65.00, 'tax_type_code'=>'B','item_category'=>'50131700','category_id'=>$dairy->id,'stock_quantity'=>100,'buying_price'=>52.00],
            ['name'=>'Eggs Tray (30)',      'sku'=>'DRY-EGG', 'price'=>580.00,'tax_type_code'=>'B','item_category'=>'50131900','category_id'=>$dairy->id,'stock_quantity'=>20, 'buying_price'=>460.00],
            ['name'=>'Yoghurt 500g',       'sku'=>'DRY-YOG', 'price'=>120.00,'tax_type_code'=>'A','item_category'=>'50131701','category_id'=>$dairy->id,'stock_quantity'=>40, 'buying_price'=>90.00],
            ['name'=>'Omo Powder 500g',    'sku'=>'PC-OMO',  'price'=>120.00,'tax_type_code'=>'A','item_category'=>'53131600','category_id'=>$care->id, 'stock_quantity'=>60, 'buying_price'=>88.00],
            ['name'=>'Dettol Soap',        'sku'=>'PC-DTL',  'price'=>85.00, 'tax_type_code'=>'A','item_category'=>'53131602','category_id'=>$care->id, 'stock_quantity'=>80, 'buying_price'=>60.00],
            ['name'=>'Tusker Lager 500ml', 'sku'=>'ALC-TSK', 'price'=>250.00,'tax_type_code'=>'E','item_category'=>'50202600','category_id'=>$alc->id,  'stock_quantity'=>120,'buying_price'=>180.00],
            ['name'=>'White Cap 500ml',    'sku'=>'ALC-WCP', 'price'=>250.00,'tax_type_code'=>'E','item_category'=>'50202600','category_id'=>$alc->id,  'stock_quantity'=>100,'buying_price'=>180.00],
        ];

        foreach ($products as $p) Product::firstOrCreate(['sku' => $p['sku']], $p);

        $this->command->info('Seeded successfully.');
        $this->command->info('Admin:   admin@demo.co.ke / password');
        $this->command->info('Manager: manager@demo.co.ke / password');
        $this->command->info('Cashier: cashier@demo.co.ke / password');
    }
}
