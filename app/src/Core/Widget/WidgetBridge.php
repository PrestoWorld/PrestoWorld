<?php

declare(strict_types=1);

namespace PrestoWorld\Core\Widget;

/**
 * WidgetBridge — replaces WP_Widget (spec 10 §10.5).
 */
class WidgetBridge
{
    public string $id_base = '';
    public string $name = '';
    public string $option_name = '';

    /** @var array<string, mixed> */
    public array $widgetOptions = [];

    /** @var array<string, mixed> */
    private array $settings = [];

    /** @var array<string, mixed> */
    private array $updated = [];

    /** @param array<string, mixed> $args */
    public function __construct(array $args = [])
    {
        if (isset($args['id_base']) && is_string($args['id_base'])) {
            $this->id_base = $args['id_base'];
        }

        if (isset($args['name']) && is_string($args['name'])) {
            $this->name = $args['name'];
        }

        if (isset($args['option_name']) && is_string($args['option_name'])) {
            $this->option_name = $args['option_name'];
        }

        if (isset($args['widget_options']) && is_array($args['widget_options'])) {
            $this->widgetOptions = self::stringKeyed($args['widget_options']);
        }

        if (isset($args['settings']) && is_array($args['settings'])) {
            $this->settings = self::stringKeyed($args['settings']);
        }
    }

    public function idBase(): string
    {
        return $this->id_base;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @param array<string, mixed> $args
     * @param array<string, mixed> $instance
     */
    public function widget(array $args, array $instance): void
    {
    }

    /**
     * @param array<string, mixed> $newInstance
     * @param array<string, mixed> $oldInstance
     * @return array<string, mixed>
     */
    public function update(array $newInstance, array $oldInstance): array
    {
        $this->updated = $newInstance;

        return $newInstance;
    }

    /**
     * @param array<string, mixed> $instance
     */
    public function form(array $instance): void
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function get_settings(): array
    {
        return $this->settings;
    }

    /**
     * @return array<string, mixed>
     */
    public function get_updated(): array
    {
        return $this->updated;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function _register_one(?string $id = null, array $options = []): void
    {
        if ($id !== null && $id !== '') {
            $this->id_base = $id;
        }

        $this->widgetOptions = array_merge($this->widgetOptions, self::stringKeyed($options));
    }

    public function _register(): void
    {
    }

    /**
     * @param array<mixed, mixed> $value
     * @return array<string, mixed>
     */
    private static function stringKeyed(array $value): array
    {
        $result = [];
        foreach ($value as $key => $item) {
            if (is_string($key)) {
                $result[$key] = $item;
            }
        }

        return $result;
    }
}
