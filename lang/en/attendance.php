<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Attendance Module — English
    |--------------------------------------------------------------------------
    */

    // Navigation
    'navigation_group'   => 'Attendance',
    'navigation_label'   => 'Attendance Logs',
    'model_label'        => 'Attendance Log',
    'plural_model_label' => 'Attendance Logs',

    // Form Sections
    'check_in_section'  => 'Attendance Data',
    'financial_section' => 'Financial Impact',
    'gps_section'       => 'GPS Location Data',

    // Fields
    'employee'        => 'Employee',
    'branch'          => 'Branch',
    'date'            => 'Date',
    'check_in_time'   => 'Check-in Time',
    'check_out_time'  => 'Check-out Time',
    'status'          => 'Status',
    'delay_minutes'   => 'Delay Minutes',
    'cost_per_minute' => 'Cost Per Minute',
    'delay_cost'      => 'Delay Cost',
    'overtime_minutes' => 'Overtime Minutes',
    'overtime_value'   => 'Overtime Value',
    'worked_minutes'   => 'Worked Minutes',
    'check_in_distance' => 'Check-in Distance',
    'within_geofence' => 'Within Geofence',
    'manual_entry'    => 'Manual Entry',
    'notes'           => 'Notes',
    'meters'          => 'm',
    'min'             => 'min',
    'sar'             => 'SAR',
    'sar_min'         => 'SAR/min',

    // Statuses
    'status_present'  => 'Present',
    'status_late'     => 'Late',
    'status_absent'   => 'Absent',
    'status_on_leave' => 'On Leave',
    'status_holiday'  => 'Holiday',
    'status_remote'   => 'Remote',
    'status_half_day' => 'Half Day',

    // Filters
    'from_date'             => 'From Date',
    'until_date'            => 'Until Date',
    'date_range'            => 'Date Range',
    'with_financial_loss'   => 'With Financial Loss',

    // Dashboard Widget
    'today_present'         => 'Present Today',
    'today_late'            => 'Late Today',
    'today_absent'          => 'Absent Today',
    'today_delay_losses'    => 'Delay Losses Today',
    'today_overtime_value'  => 'Overtime Value Today',
    'out_of_total'          => 'out of :total employees',
    'late_warning'          => 'Late arrivals detected!',
    'no_late'               => 'No late arrivals ✓',
    'financial_impact_today' => 'Today\'s financial impact',
    'overtime_at_1_5x'      => 'at 1.5× cost-per-minute rate',

    // API Messages
    'check_in_success'   => 'Check-in recorded successfully',
    'check_out_success'  => 'Check-out recorded successfully',
    'not_checked_in'     => 'Not checked in today yet',
    'today_status'       => 'Today\'s status',
    'outside_geofence'   => 'You are outside the allowed geofence. Distance: :distance m (Allowed: :radius m)',
    'no_branch_assigned' => 'No branch assigned to this employee',
];
