<?php
require_once __DIR__ . '/../support/Auth.php';
require_once __DIR__ . '/../models/SiteSettings.php';

class PageLayoutService
{
    public static function getData(): array
    {
        Auth::boot();
        $newReportCounts = [];

        if (Auth::canModerate()) {
            require_once __DIR__ . '/../models/Report.php';
            $newReportCounts = Report::getUnreadSubjectCounts((int) Auth::user()['id']);
        }

        return [
            'newReportCounts' => $newReportCounts,
            'primaryColor' => SiteSettings::getColor('color_primary', '#FF5A1F'),
            'secondaryColor' => SiteSettings::getColor('color_secondary', '#D6FF3F'),
            'footerContact' => trim(SiteSettings::get('contact')),
        ];
    }
}
