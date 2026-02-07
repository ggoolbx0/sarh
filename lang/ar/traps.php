<?php

return [
    // Navigation
    'navigation_group'      => 'الأمان',
    'navigation_label'      => 'المصائد الأمنية',
    'model_label'           => 'مصيدة',
    'plural_model_label'    => 'المصائد',
    'interactions_label'    => 'تفاعلات المصائد',
    'interaction_model_label' => 'تفاعل',
    'interaction_plural_label' => 'التفاعلات',

    // Form labels
    'trap_details'    => 'تفاصيل المصيدة',
    'risk_config'     => 'إعدادات المخاطر',
    'name_ar'         => 'الاسم بالعربية',
    'name_en'         => 'الاسم بالإنجليزية',
    'trap_code'       => 'كود المصيدة',
    'trap_code_helper' => 'مثال: SALARY_PEEK',
    'description_ar'  => 'الوصف بالعربية',
    'description_en'  => 'الوصف بالإنجليزية',
    'risk_weight'     => 'وزن الخطورة',
    'fake_response_type' => 'نوع الاستجابة المزيفة',
    'is_active'       => 'مفعّلة',
    'total_triggers'  => 'إجمالي التفعيلات',

    // Interaction fields
    'interaction_details' => 'تفاصيل التفاعل',
    'review_section'   => 'المراجعة',
    'employee'         => 'الموظف',
    'employee_id'      => 'رقم الموظف',
    'trap_type'        => 'نوع المصيدة',
    'page_url'         => 'رابط الصفحة',
    'ip_address'       => 'عنوان IP',
    'risk_level'       => 'مستوى الخطورة',
    'risk_score'       => 'نقاط المخاطر',
    'is_reviewed'      => 'تمت المراجعة',
    'review_notes'     => 'ملاحظات المراجعة',
    'triggered_at'     => 'وقت التفعيل',

    // Risk levels
    'risk_levels' => [
        'low'      => 'منخفض',
        'medium'   => 'متوسط',
        'high'     => 'مرتفع',
        'critical' => 'حرج',
    ],

    // Response types
    'response_types' => [
        'success' => 'نجاح',
        'error'   => 'خطأ',
        'warning' => 'تحذير',
    ],

    // Widget
    'risk_widget_title' => 'أعلى الموظفين خطورة',

    // Fake response messages
    'responses' => [
        'salary_title'     => 'جدول رواتب الزملاء',
        'privilege_granted' => 'تم منحك صلاحيات مؤقتة — سيتم التفعيل خلال 60 ثانية',
        'system_paused'    => 'تم تعطيل نظام الحضور مؤقتاً لمدة 24 ساعة',
        'export_complete'  => 'اكتمل التصدير — جارٍ تحميل الملف',
        'generic_success'  => 'تمت العملية بنجاح',
    ],

    // Fake names for salary table
    'fake_names' => [
        'employee_1' => 'أحمد الفهد',
        'employee_2' => 'سارة القحطاني',
        'employee_3' => 'فهد العتيبي',
    ],
];
