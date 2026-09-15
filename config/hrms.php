<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HRMS Attendance Geofence Range (in meters)
    |--------------------------------------------------------------------------
    |
    | Defines the default radius in meters around the office/branch within which
    | an employee is considered "Inside Office". Beyond this distance, attendance
    | is treated as "Outside Office" and requires an outside-office request.
    |
    */
    'attendance_radius_meters' => (float) env('HRMS_ATTENDANCE_RADIUS_METERS', 50.0),

    /*
    |--------------------------------------------------------------------------
    | HRMS Attendance GPS Tolerance Buffer (in meters)
    |--------------------------------------------------------------------------
    |
    | Accommodates normal civilian indoor GPS drift and satellite variance
    | (typically 5m–15m). An employee within (radius + tolerance) is accepted
    | as inside the office without falsely requiring outside-office approval.
    |
    */
    'attendance_gps_tolerance_meters' => (float) env('HRMS_ATTENDANCE_GPS_TOLERANCE_METERS', 15.0),
];
