<?php

/*
*  Plugin Name: Affiliate Link Replacement
*  Description: Replaces the redirect links in posts with the actual affiliate links
*  Author: Abhinav R
*/

/*
*  parse the existence of /go/* in post_content
*  check for its redirect in Redirect Manager
*  Replace the with actual affiliate link
*  add rel="nofollow noopener noreferrer" tag
*/

function insert_affiliate_links($content) {
    global $wpdb;

    // get all links from post_content and filter affiliate links

    $a_links = array();

    $dom = new DOMDocument();

    @ $dom->loadHTML( mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NODEFDTD );
    $links = $dom->getElementsByTagName('a');

    foreach($links as $l) {
        $href = $l->getAttribute('href');
        if (strpos($href, '/go/')) {
            $href_parts = explode("/", $href);
            $k = array_search('go', $href_parts) + 1;
            $a_links[] = array(
                "node" => $l,
                "match_url" => "/go/" . $href_parts[$k]
            );
        }
    }

    if (!empty($a_links)) {

        // get redirects links from database

        $conditions = '';

        foreach ($a_links as $a) {
            $conditions .= 'match_url="' . $a["match_url"] . '" OR ';
        }
        $conditions = rtrim($conditions, " OR ");

        $query = "SELECT * FROM {$wpdb->prefix}redirection_items WHERE {$conditions}";

        $redirect_urls = $wpdb->get_results($query, ARRAY_A);

    }

    if(!empty($redirect_urls)) {

        // replace urls

        foreach($redirect_urls as $r) {
            foreach($a_links as $a) {
                if( $a['match_url'] === $r['match_url']) {
                    $a['node']->setAttribute('href', $r['action_data']);
                    $a['node']->setAttribute('rel', 'nofollow noreferrer noopener');
                    $a['node']->setAttribute('class', 'affiliate-link');
                }
            }
        }

        $content = @ $dom->saveHTML();
        $content = str_replace("<html><body>", "", $content);
        $content = str_replace("</body></html>", "", $content);
    }

    return $content;
}

add_filter("the_content", "insert_affiliate_links");