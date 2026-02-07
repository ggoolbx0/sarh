<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

class BadgesSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            // --- Attendance Badges ---
            [
                'slug'          => 'early_bird',
                'name_ar'       => 'الطائر المبكر',
                'name_en'       => 'Early Bird',
                'description_ar'=> 'حضر قبل موعد الدوام لمدة 5 أيام متتالية',
                'description_en'=> 'Checked in before shift start for 5 consecutive days',
                'icon'          => 'heroicon-o-sun',
                'color'         => '#f59e0b',
                'category'      => 'attendance',
                'points_reward' => 50,
                'criteria'      => ['type' => 'streak', 'condition' => 'early_checkin', 'count' => 5],
            ],
            [
                'slug'          => 'punctuality_master',
                'name_ar'       => 'سيد الانضباط',
                'name_en'       => 'Punctuality Master',
                'description_ar'=> 'شهر كامل بدون تأخير',
                'description_en'=> 'Full month with zero delay minutes',
                'icon'          => 'heroicon-o-clock',
                'color'         => '#10b981',
                'category'      => 'attendance',
                'points_reward' => 200,
                'criteria'      => ['type' => 'monthly', 'condition' => 'zero_delay', 'count' => 1],
            ],
            [
                'slug'          => 'iron_streak_7',
                'name_ar'       => 'سلسلة حديدية (7)',
                'name_en'       => 'Iron Streak (7)',
                'description_ar'=> '7 أيام متتالية بدون تأخير',
                'description_en'=> '7 consecutive on-time days',
                'icon'          => 'heroicon-o-fire',
                'color'         => '#ef4444',
                'category'      => 'attendance',
                'points_reward' => 70,
                'criteria'      => ['type' => 'streak', 'condition' => 'on_time', 'count' => 7],
            ],
            [
                'slug'          => 'iron_streak_30',
                'name_ar'       => 'سلسلة ذهبية (30)',
                'name_en'       => 'Golden Streak (30)',
                'description_ar'=> '30 يوم متتالي بدون تأخير',
                'description_en'=> '30 consecutive on-time days',
                'icon'          => 'heroicon-o-star',
                'color'         => '#eab308',
                'category'      => 'attendance',
                'points_reward' => 500,
                'criteria'      => ['type' => 'streak', 'condition' => 'on_time', 'count' => 30],
            ],

            // --- Finance Badges ---
            [
                'slug'          => 'zero_loss',
                'name_ar'       => 'صفر خسائر',
                'name_en'       => 'Zero Loss',
                'description_ar'=> 'شهر كامل بدون أي خسارة مالية',
                'description_en'=> 'Full month with zero financial loss',
                'icon'          => 'heroicon-o-banknotes',
                'color'         => '#22c55e',
                'category'      => 'finance',
                'points_reward' => 300,
                'criteria'      => ['type' => 'monthly', 'condition' => 'zero_loss', 'count' => 1],
            ],
            [
                'slug'          => 'cost_saver',
                'name_ar'       => 'موفر التكاليف',
                'name_en'       => 'Cost Saver',
                'description_ar'=> 'أكثر من 50 ساعة عمل إضافية في الشهر',
                'description_en'=> 'More than 50 overtime hours in a month',
                'icon'          => 'heroicon-o-currency-dollar',
                'color'         => '#3b82f6',
                'category'      => 'finance',
                'points_reward' => 150,
                'criteria'      => ['type' => 'monthly', 'condition' => 'overtime_hours', 'min' => 50],
            ],

            // --- Performance Badges ---
            [
                'slug'          => 'top_performer',
                'name_ar'       => 'الأداء المتميز',
                'name_en'       => 'Top Performer',
                'description_ar'=> 'أفضل موظف في الفرع لهذا الشهر',
                'description_en'=> 'Best performing employee in branch this month',
                'icon'          => 'heroicon-o-trophy',
                'color'         => '#a855f7',
                'category'      => 'performance',
                'points_reward' => 500,
                'criteria'      => ['type' => 'ranking', 'condition' => 'branch_top', 'rank' => 1],
            ],

            // --- Special Badges ---
            [
                'slug'          => 'integrity_champion',
                'name_ar'       => 'بطل النزاهة',
                'name_en'       => 'Integrity Champion',
                'description_ar'=> 'اجتاز جميع اختبارات النزاهة بنجاح',
                'description_en'=> 'Passed all integrity trap tests successfully',
                'icon'          => 'heroicon-o-shield-check',
                'color'         => '#14b8a6',
                'category'      => 'special',
                'points_reward' => 1000,
                'criteria'      => ['type' => 'special', 'condition' => 'zero_trap_failures'],
            ],
        ];

        foreach ($badges as $badge) {
            Badge::updateOrCreate(
                ['slug' => $badge['slug']],
                $badge
            );
        }
    }
}
