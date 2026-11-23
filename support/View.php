<?php

class View
{
    public static function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        include dirname(__DIR__) . '/views/' . $view . '.php';
    }

    public static function renderPage(string $view, array $data = []): void
    {
        require_once dirname(__DIR__) . '/services/PageLayoutService.php';
        self::render($view, array_merge(PageLayoutService::getData(), $data));
    }
}
