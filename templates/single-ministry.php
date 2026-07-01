<?php

namespace TSJIPPY\LOCATIONS;

use TSJIPPY;

/**
 * The Template for displaying all single locations
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!isset($skipHeader) || !$skipHeader) {
    get_header();
}

wp_enqueue_style('tsjippy_template');

?>
<div id="primary">
    <main id="main">
        <?php
        while (have_posts()) :
            the_post();
            include(__DIR__ . '/content.php');

            //showMedia();

            // Show any projects linked to this
            projectList();

            // Show the people working here
            ministryDescription();

            showRelevantPages();
        endwhile;

        echo wp_kses_post(apply_filters('tsjippy-single-template-bottom', '', 'location'));
        ?>
    </main>

    <?php TSJIPPY\showComments(); ?>
</div>

<?php

get_sidebar();

if (!isset($skipFooter) || !$skipFooter) {
    get_footer();
}

/**
 * Default content for ministry pages
 */
function ministryDescription()
{
    $postId     = get_the_ID();
    // Show sub ministry gallery
    $ministry    = get_the_title($postId);
    $args        = array(
        'post_parent' => $postId, // The parent id.
        'post_type'   => 'page',
        'post_status' => 'publish',
        'order'       => 'ASC',
    );
    $childPages         = get_children($args, ARRAY_A);
    if ($childPages) {
?>
        <p>
            <strong>Some of our $ministry are:</strong>
        </p>
        <ul>
            <?php
            foreach ($childPages as $childPage) {
            ?>
                <li>
                    <a href='<?php echo esc_url($childPage['guid']); ?>'>
                        <?php echo esc_html($childPage['post_title']); ?>
                    </a>
                </li>
            <?php
            }
            ?>
        </ul>
        <br>
        <br>
    <?php
    }

    getLocationEmployees($postId, true);
}

function projectList()
{
    $projects = get_posts([
        'post_type'         => 'project',
        'posts_per_page'    => -1,
        'post_status'       => 'publish',
        'orderby'           => 'title',
        'order'             => 'ASC',
        'meta_query'        => array(
            array(
                'key'       => 'tsjippy_ministry',
                'value'     => get_the_ID(),
                'compare'   => '='
            )
        )
    ]);

    if (empty($projects)) {
        return '';
    }

    ?>
    <div class='projects-wrapper'>
        <h4>
            Projects linked to this ministry are:
        </h4>
        <ul>
            <?php
            foreach ($projects as $project) {
                $url    = get_permalink($project->ID);
            ?>
                <li>
                    <a href='<?php echo esc_url($url); ?>'>
                        <?php echo esc_html($project->post_title); ?>
                    </a>
                </li>
            <?php
            }
            ?>
        </ul>
    </div>
    <br>
    <?php
}

function showMedia()
{
    // Show relevant media
    $gradient   = SETTINGS['gallery-background-color-gradient'] ?? false;

    $cats       = [];
    $categories = get_the_terms(get_the_ID(), 'locations');
    foreach ($categories as $cat) {
        if (count($categories) > 1 && $cat->slug == 'ministry') {
            continue;
        }

        $cats[] = $cat->slug;
    }

    $color        = SETTINGS['media-gallery-background-color'] ?? false;
    $mediaGallery = new TSJIPPY\MEDIAGALLERY\MediaGallery(['image'], 6, $cats, true, 1, '', $color, $gradient);

    if (($_POST['switch-gallery'] ?? '') == 'filter') {
        $mediaGallery->filterableMediaGallery(true);
        $value  = 'gallery';
        $text   = 'View less';
    } else {
        $mediaGallery->mediaGallery(showDescription: false, echo: true);
        $value  = 'filter';
        $text   = 'View more media';
    }

    if ($mediaGallery->total > 3) {
    ?>
        <form method='post' style='text-align: center; padding-bottom:10px; <?php echo esc_attr($mediaGallery->style); ?>'>
            <button class='small button' name='switch-gallery' value='<?php echo esc_attr($value); ?>'>
                <?php echo esc_html($text); ?>
            </button>
        </form>
<?php
    }
}

function showRelevantPages()
{
    if (!empty(get_children(['post_parent' => get_the_ID()]))) {
        $cats['location']    = ['locations' => []];

        $categories    = get_the_terms(get_the_ID(), 'locations');

        foreach ($categories as $cat) {
            if (count($categories) > 1 && $cat->slug == 'ministry') {
                continue;
            }

            $cats['location']['locations'][]    = $cat->slug;
        }


        $gradient        = SETTINGS['gallery-background-color-gradient'] ?? false;

        TSJIPPY\PAGEGALLERY\pageGallery('Related Ministries', [get_post_type()], 3, $cats, 60, true, SETTINGS['page-gallery-background-color'] ?? false, $gradient, true);
    }
}

/* function addGallery($mediaGallery) {
    $content    = "<!-- wp:gallery {'linkTo':'none'} -->";
        $content    .= "<figure class='wp-block-gallery has-nested-images columns-default is-cropped'>";
            foreach ($mediaGallery->posts as $post) {
                $url    = wp_get_attachment_image_url($post->ID);
                $content    .= "<!-- wp:image {'id':$post->ID,'sizeSlug':'large','linkDestination':'media'} -->";
                    $content    .= "<figure class='wp-block-image size-large'>";
                        $content    .= "<img src='$url' alt='' class='wp-image-$post->ID'/>";
                    $content    .= "</figure>";
                $content    .= "<!-- /wp:image -->";
            }
        $content    .= "</figure>";
    $content    .= "<!-- /wp:gallery -->";

    $html    = '';
    $blocks = parse_blocks($content);
    foreach ($blocks as $block) {
        $html    .= render_block($block);
    }

 echo $html;
} */