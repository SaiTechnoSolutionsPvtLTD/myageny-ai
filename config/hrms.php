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
];
