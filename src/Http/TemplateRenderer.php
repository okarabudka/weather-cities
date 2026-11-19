<?php

declare(strict_types=1);

namespace WeatherCities\Http;

/** Plain PHP templates: a page template rendered into templates/layout.php. */
final readonly class TemplateRenderer
{
    public function __construct(
        private string $directory,
    ) {
    }

    public static function e(string|int|float|null $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * @param array<string, mixed> $variables
     */
    public function render(string $template, array $variables = []): string
    {
        $content = $this->include($template, $variables);

        return $this->include('layout', ['content' => $content]);
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function include(string $template, array $variables): string
    {
        $file = $this->directory . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException(sprintf('Template "%s" not found.', $template));
        }
        $variables['e'] = self::e(...);

        // A static closure gives the template its own scope: only the passed variables, no $this.
        $render = static function (string $__file, array $__variables): void {
            extract($__variables, EXTR_SKIP);
            require $__file;
        };

        $level = ob_get_level();
        ob_start();
        try {
            $render($file, $variables);

            return (string) ob_get_clean();
        } finally {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
        }
    }
}
