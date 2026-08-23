<?php

namespace App\Enums;

enum ReportType: string
{
    case Audit = 'audit';
    case Development = 'development';
    case BugFix = 'bug_fix';
    case Deployment = 'deployment';
    case Security = 'security';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Audit => 'حسابرسی',
            self::Development => 'توسعه',
            self::BugFix => 'رفع اشکال',
            self::Deployment => 'استقرار',
            self::Security => 'امنیت',
            self::Other => 'سایر',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Audit => 'bg-purple-50 text-purple-700 ring-purple-200',
            self::Development => 'bg-brand-50 text-brand-700 ring-brand-200',
            self::BugFix => 'bg-red-50 text-red-700 ring-red-200',
            self::Deployment => 'bg-green-50 text-green-700 ring-green-200',
            self::Security => 'bg-amber-50 text-amber-700 ring-amber-200',
            self::Other => 'bg-gray-100 text-gray-600 ring-gray-200',
        };
    }

    /** @return array<string, string> value => label */
    public static function options(): array
    {
        return array_column(self::cases(), 'value', 'name') === []
            ? []
            : collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
