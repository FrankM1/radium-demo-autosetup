<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$posts = array(
    array(
        'post_title' => 'Video Style 1 - Grid Content Bottom',
        'post_content' => 'It may be that the primal source of all those pictorial delusions will be found among the oldest Hindoo, Egyptian, and Grecian sculptures.

For ever since those inventive but unscrupulous times when on the marble panellings of temples, the pedestals of statues, and on shields, medallions, cups, and coins, the dolphin was drawn in scales of chain-armor like Saladin’s, and a helmeted head like St. George’s; ever since then has something of the same sort of license prevailed, not only in most popular pictures of the whale, but in many scientific presentations of him.

Now, by all odds, the most ancient extant portrait anyways purporting to be the whale’s, is to be found in the famous cavern-pagoda of Elephanta, in India. The Brahmins maintain that in the almost endless sculptures of that immemorial pagoda, all the trades and pursuits, every conceivable avocation of man, were prefigured ages before any of them actually came into being.',
        'post_excerpt' => 'A grid portfolio item with content placed on the bottom',
        'post_format' => 'gallery',
        'gallery' => array(
            'meta_key' => '_radium_gallery_images',
            'ids' => false
        ),
        'taxonomy' => array(
            'portfolio_category' => array(
                'grid', 'gallery', 'photography',
            ),
            'portfolio_tag' => array(
                'style 1',
            ),
        ),
        'meta' => array(
            '_radium_appearrance_in_loop' => '',
            '_radium_portfolio_content_layout' => '1',
            '_radium_gallery_lighbox' => '1',
            '_radium_gallery_type' => 'carousel',
            '_radium_date' => '2017-01-29',
            '_radium_client' => 'Awesome Client',
            '_radium_url_text' => 'Link to website',
            '_radium_url' => '#',
        )
    ),
);

return $posts;
