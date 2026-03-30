<?php

namespace Database\Seeders;

use App\Models\ElectionList;
use Illuminate\Database\Seeder;

class ElectionListsSeeder extends Seeder
{
    public function run(): void
    {
        $lists = [
            ['name' => 'ارض البشارة', 'code' => 'LIST01', 'estimated_votes' => 0, 'is_our_list' => false],
            ['name' => 'شباب البلد', 'code' => 'LIST02', 'estimated_votes' => 0, 'is_our_list' => false],
            ['name' => 'بيت ساحور للجميع', 'code' => 'LIST03', 'estimated_votes' => 0, 'is_our_list' => true],
            ['name' => 'العهد والتنمية', 'code' => 'LIST04', 'estimated_votes' => 0, 'is_our_list' => false],
            ['name' => 'البناء والتنمية', 'code' => 'LIST05', 'estimated_votes' => 0, 'is_our_list' => false],
        ];

        foreach ($lists as $list) {
            ElectionList::updateOrCreate(
                ['code' => $list['code']],
                $list
            );
        }
    }
}
