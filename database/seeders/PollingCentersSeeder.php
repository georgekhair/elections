<?php

namespace Database\Seeders;

use App\Models\PollingCenter;
use Illuminate\Database\Seeder;

class PollingCentersSeeder extends Seeder
{
    public function run(): void
    {
        $centers = [
            [
                'name' => 'مدرسة الرعاة الثانوية الأرثوذكسية',
                'code' => 'PC01',
            ],
            [
                'name' => 'مدرسة بنات الناصرة الأساسية',
                'code' => 'PC02',
            ],
            [
                'name' => 'مدرسة بنات بيت ساحور الثانوية',
                'code' => 'PC03',
            ],
            [
                'name' => 'مدرسة ذكور بيت ساحور الثانوية',
                'code' => 'PC04',
            ],
            [
                'name' => 'مدرسة ذكور طبريا الأساسية',
                'code' => 'PC05',
            ],
        ];

        foreach ($centers as $center) {
            PollingCenter::updateOrCreate(
                ['code' => $center['code']],
                $center
            );
        }
    }
}
