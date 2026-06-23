<?php

return [

    'backup' => [
        'name' => env('APP_NAME', 'فندق السعودي'),

        'source' => [
            'files' => [
                'include' => [
                    storage_path('app/private'),
                ],
                'exclude' => [
                    storage_path('app/private/backup-temp'),
                ],
                'follow_links' => false,
                'ignore_unreadable_directories' => false,
                'relative_path' => null,
            ],

            'databases' => [
                env('DB_CONNECTION', 'mysql'),
            ],
        ],

        'database_dump_compressor' => \Spatie\DbDumper\Compressors\GzipCompressor::class,

        'database_dump_file_timestamp_format' => 'Y-m-d_H-i-s',

        'database_dump_filename_base' => 'database',

        'database_dump_file_extension' => '',

        'destination' => [
            'compression_method' => ZipArchive::CM_DEFAULT,
            'compression_level'  => 9,
            'filename_prefix'    => 'hotel-backup-',
            'disks' => [
                'local',
            ],
        ],

        'temporary_directory' => storage_path('app/backup-temp'),

        'password' => env('BACKUP_ARCHIVE_PASSWORD'),

        'encryption' => 'default',

        'tries' => 3,

        'retry_delay' => 10,
    ],

    'notifications' => [
        'notifications' => [
            \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class         => ['mail'],
            \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification::class        => ['mail'],
            \Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification::class     => ['mail'],
            \Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification::class   => [],
            \Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification::class    => [],
        ],

        'notifiable' => \Spatie\Backup\Notifications\Notifiable::class,

        'mail' => [
            'to'   => env('BACKUP_NOTIFICATION_EMAIL', env('MAIL_FROM_ADDRESS', 'admin@hotel.com')),
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'noreply@hotel.com'),
                'name'    => env('MAIL_FROM_NAME', 'Hotel System'),
            ],
        ],

        'slack' => [
            'webhook_url' => '',
            'channel'     => null,
            'username'    => null,
            'icon'        => null,
        ],

        'discord' => [
            'webhook_url' => '',
            'username'    => '',
            'avatar_url'  => '',
        ],
    ],

    'monitor_backups' => [
        [
            'name'  => env('APP_NAME', 'فندق السعودي'),
            'disks' => ['local'],
            'health_checks' => [
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class          => 2,
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes::class => 2000,
            ],
        ],
    ],

    'cleanup' => [
        'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days'                          => 7,
            'keep_daily_backups_for_days'                        => 30,
            'keep_weekly_backups_for_weeks'                      => 12,
            'keep_monthly_backups_for_months'                    => 6,
            'keep_yearly_backups_for_years'                      => 2,
            'delete_oldest_backups_when_using_more_megabytes_than' => 2000,
        ],

        'tries'       => 3,
        'retry_delay' => 10,
    ],

];
