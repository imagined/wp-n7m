<?php
/**
 * Plugin Name: WordPress Numeronyms
 * Plugin URI:  https://github.com/imagined/wp-n7m
 * Description: Numeronyms redirects for WordPress post permalinks.
 * Author:      Imagined | Robin Withaar
 * Author URI:  https://imagined.nl
 * Version:     0.1
 *
 * @package WP_N7M
 */

namespace Imagined;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once(__DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

use WP_Query;

class WP_N7M
{
    public $db;
    public $queue;

    const N7M = 'n7m';

    public function __construct()
    {
        $this->db = new WP_N7M_DB();

        // Create table on activation
        add_action('activated_plugin', [$this, 'activateN7M']);

        // Listen for numeronyms redirects
        add_action('init', [$this, 'checkN7M']);

        // Display numeronym redirect after permalink in admin
        add_filter('get_sample_permalink_html', [$this, 'displayN7M']);
    }

    public function activateN7M()
    {
        $this->db->createTable();

        foreach ($this->getPermalinks() as $permalink) {
            $this->db->saveNumeronym($this->getRequest($permalink));
        }
    }

    public function getRequest($url = null)
    {
        if ($url) {
            return trim(parse_url($url, PHP_URL_PATH), '/');
        }
        return trim($_SERVER['REQUEST_URI'], '/');
    }

    public function getPermalinks($post_type = ['post', 'page'])
    {
        //set_time_limit(0);
        $permalinks = [];
        // TODO: all post types in background task
        $loop = new WP_Query([
            'post_type'      => $post_type,
            'posts_per_page' => -1
        ]);
        if ($loop->have_posts()) {
            while ($loop->have_posts()) {
                $loop->the_post();
                $permalinks[] = get_permalink();
            }
        }
        return $permalinks;
    }

    public function checkN7M()
    {
        if (in_array($this->getRequest(), $this->db->getAllNumeronyms())) {
            $this->redirectN7M();
        }
    }

    public function redirectN7M()
    {
        exit(wp_redirect('/' . $this->db->getRedirect() . '/'));
    }

    public function displayN7M($permalink_html)
    {

        if ($numeronym = $this->getN7M(get_permalink())) {
            $numeronym_html = '
            <br><strong>Numeronym:</strong>
            <span id="sample-n7m">
                <a href="%1$s/%2$s/">%1$s/<span id="editable-post-n7m">%2$s</span>/</a>
            </span>';
            return $permalink_html . sprintf($numeronym_html, site_url(), $numeronym);
        }
        return $permalink_html;
    }

    public function getN7M($permalink)
    {
        $request = $this->getRequest($permalink);
        return $this->db->getNumeronym($request);
    }

    public function generateN7M($request) {
        $specialChars = ['\/', '-', '.', '_', '~', '!', '$', '&', '\'', '(', ')', '*', '+', ',', ';', '=', ':', '@', '|'];
        $patternChars = implode('|\\', $specialChars);
        $regexPattern = "/(.*?)(?:$patternChars|$)/";
        preg_match_all($regexPattern, $request, $matches);
        $parts = array_slice($matches[0], 0, -1);
        $N7M = '';
        foreach ($parts as $part) {
            if (in_array(substr($part, -1, 1), $specialChars)) {
                if (strlen($part) > 5) {
                    $delimiter = substr($part, -1, 1);
                    $part = substr($part, 0, 1) . (strlen($part) - 3) . substr($part, -2, 1) . $delimiter;
                }
            } elseif (strlen($part) >= 5) {
                $part = substr($part, 0, 1) . (strlen($part) - 2) . substr($part, -1, 1);
            }
            $N7M .= $part;
        }
        return $N7M;
    }
}

new WP_N7M();