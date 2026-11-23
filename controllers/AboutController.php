<?php
class AboutController
{
    public static function show(): void
    {
        require_once dirname(__DIR__) . '/models/SiteSettings.php';
        $description = trim(SiteSettings::get('description'));
        $rules = array_values(array_filter(
            array_map(
                static fn($rule) => trim(preg_replace('/^\d+[.)]\s*/', '', trim($rule))),
                preg_split('/\R/u', SiteSettings::get('rules')) ?: []
            ),
            static fn($rule) => $rule !== ''
        ));
        $aboutStats = SiteSettings::getAboutStats();
        require_once dirname(__DIR__) . '/support/View.php';
        View::renderPage('about', compact('description', 'rules', 'aboutStats'));
    }
}
