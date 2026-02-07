<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TrapsSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $traps = [
            [
                'name_ar'            => 'استراق رواتب الزملاء',
                'name_en'            => 'Salary Peek',
                'trap_code'          => 'SALARY_PEEK',
                'description_ar'     => 'زر مزيف يعرض جدول رواتب وهمي لاختبار محاولة الوصول غير المصرح',
                'description_en'     => 'Fake button showing dummy salary table to test unauthorized access attempts',
                'risk_weight'        => 6,
                'fake_response_type' => 'success',
                'is_active'          => true,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'name_ar'            => 'تصعيد الصلاحيات',
                'name_en'            => 'Privilege Escalation',
                'trap_code'          => 'PRIVILEGE_ESCALATION',
                'description_ar'     => 'رابط مزيف للوحة المدير يعرض رسالة نجاح كاذبة لرصد محاولات التصعيد',
                'description_en'     => 'Fake admin panel link showing false success to detect escalation attempts',
                'risk_weight'        => 9,
                'fake_response_type' => 'success',
                'is_active'          => true,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'name_ar'            => 'تعطيل النظام',
                'name_en'            => 'System Bypass',
                'trap_code'          => 'SYSTEM_BYPASS',
                'description_ar'     => 'خيار وهمي لتعطيل نظام الحضور يعرض تأكيداً كاذباً',
                'description_en'     => 'Fake option to disable attendance system showing false confirmation',
                'risk_weight'        => 8,
                'fake_response_type' => 'warning',
                'is_active'          => true,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'name_ar'            => 'تصدير البيانات',
                'name_en'            => 'Data Export',
                'trap_code'          => 'DATA_EXPORT',
                'description_ar'     => 'زر تصدير وهمي يعرض شريط تقدم ثم يعطي ملف CSV فارغ',
                'description_en'     => 'Fake export button showing progress bar then delivering empty encoded CSV',
                'risk_weight'        => 7,
                'fake_response_type' => 'success',
                'is_active'          => true,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
        ];

        DB::table('traps')->insert($traps);
    }
}
