<?php

/** 
  PLUGIN NAME: CRAWL
  Description: Crawl website
  Version 0.0.1
  Author: JB
  Author URI: https://flemking.com

 */

if (!defined('ABSPATH')) {
    die("Invalid request");
}

if (!class_exists('Crawler')) {
    /**
     * Crawler class
     */
    class Crawler
    {
        /**
         * Initializing plugin menu and settings pages
         */
        public function __construct()
        {
            add_action('admin_menu', array($this, 'crawlerMenu'));
            add_action('admin_init', array($this, 'crawlerSettings'));
        }

        /**
         * Setting up the admin menu
         * 
         * @return void
         */
        function crawlerMenu()
        {
            add_menu_page('WP Crawler', 'Crawler', 'manage_options', 'wpcrawler-menu', array($this, 'wpcrawlerPage'), 'dashicons-admin-links', 0);
            add_submenu_page('wpcrawler-menu', 'Crawl Launcher', 'Crawl Launcher', 'manage_options', 'wpcrawler-menu', array($this, 'wpcrawlerPage'));
            add_submenu_page('wpcrawler-menu', 'Crawl Options', 'Options', 'manage_options', 'wpcrawler-options', array($this, 'optionsPage'));
        }

        function wpcrawlerPage()
        {
            $next_schedule = wp_next_scheduled('crawl_cron_hook');
?>
            <div class="wrap">
                <h1>WP Crawler</h1>
                <?php if ($next_schedule) echo "Next scheduled crawl is set for: " . date("Y-m-d h:i:s", $next_schedule); ?>

                <?php echo get_option('crawl_html'); ?>
                <?php if (get_option('crawl_sitemap_file') === "1") echo "<a class='button' href='/sitemap.html'>See the Sitemap.html</a>" ?>
                <?php if (get_option('crawl_homepage_file') === "1") echo "<a class='button' href='/homepage.html'>See the Homepage.html</a>" ?>
                <?php if ($_POST['justsubmitted'] == "true") $this->handleForm() ?>
                <form method="POST" style="margin-top: 1rem;">
                    <input type="hidden" name="justsubmitted" value="true">
                    <?php wp_nonce_field('saveFilterWords', 'ourNonce') ?>

                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Launch Crawl">
                </form>
            </div>
            <?php
        }


        function handleForm()
        {
            $data = json_encode($this->start_crawler(get_site_url(), get_site_url()));
            if (wp_verify_nonce($_POST['ourNonce'], 'saveFilterWords') and current_user_can('manage_options')) {
                // update_option('plugin_words_to_filter', sanitize_text_field($_POST['plugin_words_to_filter'])); 
                if (!get_option('crawl_data')) {
                    add_option('crawl_data', $data);
                } else {
                    update_option('crawl_data', $data);
                }

                // scheduling the crawl
                if (get_option('crawl_scheduled') == "1") {
                    if (!wp_next_scheduled('crawl_cron_hook')) {
                        add_action('crawl_cron_hook', [$this, 'handleForm']);
                        wp_schedule_event(time() + 3600, 'hourly', 'crawl_cron_hook');
                    }
                } else {
                    if (wp_next_scheduled('crawl_cron_hook')) {
                        $timestamp = wp_next_scheduled('crawl_cron_hook');
                        wp_unschedule_event($timestamp, 'crawl_cron_hook');
                    }
                }

                $html = "<h1>Homepage Crawl Result</h1><ul>";
            ?>
                <div class="updated">
                    <h2>Website crawled successfully.</h2>
                    <p>Links Found:</p>
                    <p><?php
                        foreach (json_decode(get_option('crawl_data')) as $key => $value) {
                            echo "- <b>$value</b>: $key<br>";
                            $html .= '<li>';
                            $html .= "<a href=$key>" . (get_option('crawl_text') === '0' ? $value : $key) . "</a>";
                            $html .= '</li>';
                        }
                        $html .= '</ul>';

                        // Creating the sitemap file
                        if (get_option('crawl_sitemap_file') == "1") {
                            $sitemap = get_home_path() . "sitemap.html";
                            if (file_exists($sitemap)) {
                                $success = unlink($sitemap);
                                if (!$success) {
                                    throw new Exception("Cannot delete $sitemap");
                                }
                            }
                            // Writing the sitemap
                            $file = fopen($sitemap, "w") or die("Unable to open file!");
                            fwrite($file, $html);
                            fclose($file);
                        } else {
                            $sitemap = get_home_path() . "sitemap.html";
                            if (file_exists($sitemap)) {
                                $success = unlink($sitemap);
                                if (!$success) {
                                    throw new Exception("Cannot delete $sitemap");
                                }
                            }
                        }

                        // Saving the crawled data into the database
                        if (!get_option('crawl_html')) {
                            add_option('crawl_html', $html);
                        } else {
                            update_option('crawl_html', $html);
                        }
                        ?>
                    </p>
                </div>


            <?php } else { ?>
                <div class="error">
                    <p>Sorry, you do not have permission to perform that action.</p>
                </div>
            <?php
            }
        }

        function crawlerSettings()
        {
            add_settings_section('crawler-section', null, null, 'wpcrawler-options');

            add_settings_field('crawl_text', 'See links text/link', array($this, 'optionsTextHTML'), 'wpcrawler-options', 'crawler-section');
            register_setting('optionsFields', 'crawl_text', array('sanitize_callback' => 'sanitize_text_field', 'default' => '0'));

            add_settings_field('crawl_scheduled', 'Schedule the Crawl to run every hour', array($this, 'optionsScheduledHTML'), 'wpcrawler-options', 'crawler-section');
            register_setting('optionsFields', 'crawl_scheduled', array('sanitize_callback' => 'sanitize_text_field', 'default' => '1'));

            add_settings_field('crawl_sitemap_file', 'Generate Sitemap.html file', array($this, 'optionsSitemapHTML'), 'wpcrawler-options', 'crawler-section');
            register_setting('optionsFields', 'crawl_sitemap_file', array('sanitize_callback' => 'sanitize_text_field', 'default' => '1'));

            add_settings_field('crawl_homepage_file', 'Generate Homepage.html file', array($this, 'optionsHomepageHTML'), 'wpcrawler-options', 'crawler-section');
            register_setting('optionsFields', 'crawl_homepage_file', array('sanitize_callback' => 'sanitize_text_field', 'default' => '1'));
        }

        function optionsTextHTML()
        { ?>
            <select name="crawl_text" id="">
                <option value="0" <?php selected(get_option('crawl_text'), '0') ?>>Show Text</option>
                <option value="1" <?php selected(get_option('crawl_text'), '1') ?>>Show Urls</option>
            </select>
        <?php }
        function optionsScheduledHTML()
        { ?>
            <input type="checkbox" name="crawl_scheduled" value="1" <?php echo checked(get_option('crawl_scheduled'), "1"); ?>>
        <?php }
        function optionsSitemapHTML()
        { ?>

            <input type="checkbox" name="crawl_sitemap_file" value="1" <?php echo checked(get_option('crawl_sitemap_file'), "1"); ?>>

        <?php }
        function optionsHomepageHTML()
        { ?>
            <input type="checkbox" name="crawl_homepage_file" value="1" <?php echo checked(get_option('crawl_homepage_file'), "1"); ?>>
        <?php }

        function optionsPage()
        {
        ?>
            <div class="wrap">
                <h1>Crawler Options</h1>
                <form action="options.php" method="POST">
                    <?php
                    settings_errors();
                    settings_fields('optionsFields');
                    do_settings_sections('wpcrawler-options');
                    submit_button();
                    ?>
                </form>
            </div>
<?php
            // echo get_option('crawl_html');
        }


        function start_crawler($url, $base_url, $depth = 0, $maxDepth = 1000, &$crawledLinks = [])
        {
            if ($depth > $maxDepth || in_array($url, $crawledLinks)) {
                return []; // Stop crawling when maximum depth is reached (also prevent infinite loops)
            }
            $crawledLinks[] = $url;
            // echo $url . "<br>";

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($curl);
            curl_close($curl);

            // Creating the homepage file
            if (get_option('crawl_homepage_file') == "1") {
                $homepage = get_home_path() . "homepage.html";
                if (file_exists($homepage)) {
                    $success = unlink($homepage);
                    if (!$success) {
                        throw new Exception("Cannot delete $homepage");
                    }
                }
                // Writing the sitemap
                $file = fopen($homepage, "w") or die("Unable to open file!");
                fwrite($file, $result);
                fclose($file);
            } else {
                $homepage = get_home_path() . "sitemap.html";
                if (file_exists($homepage)) {
                    $success = unlink($homepage);
                    if (!$success) {
                        throw new Exception("Cannot delete $homepage");
                    }
                }
            }

            if ($result) {
                //Create a new DOM document
                $dom = new DOMDocument;

                @$dom->loadHTML($result);

                $links = $dom->getElementsByTagName('a');
                $internalLinks = [];
                foreach ($links as $link) {
                    $text = $link->nodeValue;
                    $href = $link->getAttribute('href');

                    // Check if the URL is not starting with "http" or contains a "/"
                    if (strpos($href, $url) === 0 || strpos($href, '/') === 0 || strpos($href, "http") !== 0) {
                        $internalLinks[$href] = str_replace("'", "", $text);
                    }
                }
                // Remove duplicate internal links
                $uniqueInternalLinks = array_unique($internalLinks);
                $uniqueInternalLinks = array_diff_key($uniqueInternalLinks, ["#" => "", "/" => "", "javascript:;" => "", "" => "", "javascript:void(0);" => ""]);


                // $subLinks = [];
                // foreach ($uniqueInternalLinks as $subLink => $subText) {
                //     if (strpos($subLink, $base_url) === 0) {
                //         $subsLinks[$subLink] = $this->start_crawler($subLink, '1', $depth + 1, $maxDepth, $crawledLinks);
                //     } else if (strpos($subLink, '/') === 0) {
                //         $subLinks[$subLink] = $this->start_crawler("$base_url$subLink", '1', $depth + 1, $maxDepth, $crawledLinks);
                //     } else {
                //         $subLinks[$subLink] = $this->start_crawler("$base_url/$subLink", '1', $depth + 1, $maxDepth, $crawledLinks);
                //     }
                // }
                // return $subLinks;
                return $uniqueInternalLinks;
            } else {
                return [];
            }
        }
    }
}
$crawl_plugin = new Crawler();
