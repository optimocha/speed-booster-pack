<?php

namespace Optimocha\SpeedBooster\Frontend;

defined('ABSPATH') || exit;

use Optimocha\SpeedBooster\Utils;
use WP_Background_Process;

class WarmupProcess extends WP_Background_Process
{
    protected $action = 'sbp_CacheWarmup';
    private $begun = false;

    protected function task($item): bool
    {
        $item['url'] = Utils::clear_hashes_and_question_mark($item['url']);

        $options = $item['options'] ?? [];
        $args = array_merge(
            ['blocking' => false, 'httpversion' => '1.1', 'timeout' => 0.01],
            $options
        );

        wp_remote_get($item['url'], $args);

        if ($this->begun === false) {
            $this->begun = true;
        }

        return false;
    }

    protected function complete()
    {
        delete_transient('sbp_warmup_started');
        parent::complete();
    }
}
