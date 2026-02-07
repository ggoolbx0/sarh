<?php

return [
    // Navigation
    'navigation_group'      => 'Security',
    'navigation_label'      => 'Security Traps',
    'model_label'           => 'Trap',
    'plural_model_label'    => 'Traps',
    'interactions_label'    => 'Trap Interactions',
    'interaction_model_label' => 'Interaction',
    'interaction_plural_label' => 'Interactions',

    // Form labels
    'trap_details'    => 'Trap Details',
    'risk_config'     => 'Risk Configuration',
    'name_ar'         => 'Name (Arabic)',
    'name_en'         => 'Name (English)',
    'trap_code'       => 'Trap Code',
    'trap_code_helper' => 'e.g., SALARY_PEEK',
    'description_ar'  => 'Description (Arabic)',
    'description_en'  => 'Description (English)',
    'risk_weight'     => 'Risk Weight',
    'fake_response_type' => 'Fake Response Type',
    'is_active'       => 'Active',
    'total_triggers'  => 'Total Triggers',

    // Interaction fields
    'interaction_details' => 'Interaction Details',
    'review_section'   => 'Review',
    'employee'         => 'Employee',
    'employee_id'      => 'Employee ID',
    'trap_type'        => 'Trap Type',
    'page_url'         => 'Page URL',
    'ip_address'       => 'IP Address',
    'risk_level'       => 'Risk Level',
    'risk_score'       => 'Risk Score',
    'is_reviewed'      => 'Reviewed',
    'review_notes'     => 'Review Notes',
    'triggered_at'     => 'Triggered At',

    // Risk levels
    'risk_levels' => [
        'low'      => 'Low',
        'medium'   => 'Medium',
        'high'     => 'High',
        'critical' => 'Critical',
    ],

    // Response types
    'response_types' => [
        'success' => 'Success',
        'error'   => 'Error',
        'warning' => 'Warning',
    ],

    // Widget
    'risk_widget_title' => 'Top At-Risk Employees',

    // Fake response messages
    'responses' => [
        'salary_title'     => 'Colleague Salary Table',
        'privilege_granted' => 'Temporary admin access granted — activating in 60 seconds',
        'system_paused'    => 'Attendance system paused for 24 hours',
        'export_complete'  => 'Export complete — downloading file',
        'generic_success'  => 'Operation completed successfully',
    ],

    // Fake names for salary table
    'fake_names' => [
        'employee_1' => 'Ahmed Al-Fahd',
        'employee_2' => 'Sara Al-Qahtani',
        'employee_3' => 'Fahd Al-Otaibi',
    ],
];
