<?php

require_once(WMP_PLUGIN_PATH."export/class-export.php");

class ExportPagesTest extends WP_UnitTestCase
{

    function setUp(){
        parent::setUp();

        update_option('wmpack_inactive_pages', array());
    }

    /**
     * Calling export_pages() with password protected pages returns empty
     */
    function test_export_pages_with_password_protected_pages_returns_empty()
    {
        $post_id = $this->factory->post->create(
            array(
                'post_password' => '123123',
                'post_type' => 'page'
            )
        );

        $export = new WMobilePack_Export();
        $this->assertEquals($export->export_pages(), json_encode(array('pages' => array())));

        wp_delete_post($post_id);
    }

    /**
     * Calling export_pages() with draft pages returns empty
     */
    function test_export_pages_with_draft_pages_returns_empty()
    {
        $post_id = $this->factory->post->create(
            array(
                'post_status' => 'draft',
                'post_type' => 'page'
            )
        );

        $export = new WMobilePack_Export();
        $this->assertEquals($export->export_pages(), json_encode(array('pages' => array())));

        wp_delete_post($post_id);
    }

    /**
     * Calling export_pages() with inactive pages returns empty
     */
    function test_export_pages_with_inactive_pages_returns_empty()
    {
        $post_id = $this->factory->post->create(
            array(
                'post_type' => 'page'
            )
        );

        update_option('wmpack_inactive_pages', array($post_id));

        $export = new WMobilePack_Export();
        $this->assertEquals($export->export_pages(), json_encode(array('pages' => array())));

        wp_delete_post($post_id);
    }

    /**
     * Calling export_pages() without ordered pages returns data with alphabetically ordered pages
     */
    function test_export_pages_without_ordered_pages_returns_data()
    {
        $post_id = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'post_title' => 'a',
                'post_content' => 'test content'
            )
        );

        $post_id2 = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'post_title' => 'b',
                'post_content' => 'test content'
            )
        );

        $export = new WMobilePack_Export();
        $data = json_decode($export->export_pages(), true);

        $this->assertArrayHasKey('pages', $data);
        $this->assertEquals(2, count($data['pages']));
        $this->assertEquals($post_id, $data['pages'][0]['id']);
        $this->assertEquals(1, $data['pages'][0]['order']);
        $this->assertEquals('a', $data['pages'][0]['title']);
        $this->assertEquals('', $data['pages'][0]['content']);

        $this->assertEquals($post_id2, $data['pages'][1]['id']);
        $this->assertEquals(2, $data['pages'][1]['order']);
        $this->assertEquals('b', $data['pages'][1]['title']);
        $this->assertEquals('', $data['pages'][1]['content']);

        wp_delete_post($post_id);
        wp_delete_post($post_id2);
    }

    /**
     * Calling export_pages() with ordered pages returns data
     */
    function test_export_pages_with_ordered_pages_returns_data()
    {
        $post_id = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'menu_order' => 2,
                'post_title' => 'a',
                'post_content' => 'test content',

            )
        );

        $post_id2 = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'menu_order' => 1,
                'post_title' => 'b',
                'post_content' => 'test content'
            )
        );

        $export = new WMobilePack_Export();
        $data = json_decode($export->export_pages(), true);

        $this->assertArrayHasKey('pages', $data);
        $this->assertEquals(2, count($data['pages']));
        $this->assertEquals($post_id2, $data['pages'][0]['id']);
        $this->assertEquals(1, $data['pages'][0]['order']);
        $this->assertEquals('b', $data['pages'][0]['title']);
        $this->assertEquals('', $data['pages'][0]['content']);

        $this->assertEquals($post_id, $data['pages'][1]['id']);
        $this->assertEquals(2, $data['pages'][1]['order']);
        $this->assertEquals('a', $data['pages'][1]['title']);
        $this->assertEquals('', $data['pages'][1]['content']);

        wp_delete_post($post_id);
        wp_delete_post($post_id2);
    }


    /**
     * Calling export_pages() with pages that have images returns data
     */
    function test_export_pages_with_images_returns_data()
    {
        $post_id = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'post_title' => 'Test Page',
                'post_content' => 'test content'
            )
        );

        // mock an attachment image and link it to the post
        $filename = "test_image.jpg";
        $wp_upload_dir = wp_upload_dir();

        $attachment = array(
            'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ),
            'post_mime_type' => 'image/jpeg',
            'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
            'post_status'    => 'inherit'
        );

        $attach_id = wp_insert_attachment( $attachment, $filename, $post_id );
        add_post_meta( $post_id, '_thumbnail_id', $attach_id, true );

        $export = new WMobilePack_Export();
        $data = json_decode($export->export_pages(), true);

        $this->assertArrayHasKey('pages', $data);
        $this->assertEquals(1, count($data['pages']));
        $this->assertEquals($post_id, $data['pages'][0]['id']);
        $this->assertEquals('Test Page', $data['pages'][0]['title']);
        $this->assertEquals(1, $data['pages'][0]['order']);
        $this->assertEquals('', $data['pages'][0]['content']);
        $this->assertEquals(1, $data['pages'][0]['has_content']);
        $this->assertEquals(0, $data['pages'][0]['parent_id']);

        // check image
        $this->assertArrayHasKey('image', $data['pages'][0]);
        $this->assertEquals($wp_upload_dir['baseurl'] . '/'.$filename, $data['pages'][0]['image']['src']);
        $this->assertTrue(is_numeric($data['pages'][0]['image']['width']));
        $this->assertTrue(is_numeric($data['pages'][0]['image']['height']));

        wp_delete_post($post_id);
    }


    /**
     * Calling export_pages() with pages that have parents returns data
     */
    function test_export_pages_with_parent_id_returns_data()
    {
        $post_id = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'post_title' => 'Test Page',
                'post_content' => 'test content',
                'post_parent' => 1234
            )
        );

        $export = new WMobilePack_Export();
        $data = json_decode($export->export_pages(), true);

        $this->assertArrayHasKey('pages', $data);
        $this->assertEquals(1, count($data['pages']));
        $this->assertEquals($post_id, $data['pages'][0]['id']);
        $this->assertEquals('Test Page', $data['pages'][0]['title']);
        $this->assertEquals(1, $data['pages'][0]['order']);
        $this->assertEquals('', $data['pages'][0]['content']);
        $this->assertEquals(1, $data['pages'][0]['has_content']);
        $this->assertEquals(1234, $data['pages'][0]['parent_id']);

        wp_delete_post($post_id);
    }

    /**
     * Calling export_pages() with pages that don't have content returns data
     */
    function test_export_pages_with_no_content_returns_data()
    {
        $post_id = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'post_title' => 'Test Page',
                'post_content' => ''
            )
        );

        $export = new WMobilePack_Export();
        $data = json_decode($export->export_pages(), true);

        $this->assertArrayHasKey('pages', $data);
        $this->assertEquals(1, count($data['pages']));
        $this->assertEquals($post_id, $data['pages'][0]['id']);
        $this->assertEquals('Test Page', $data['pages'][0]['title']);
        $this->assertEquals(1, $data['pages'][0]['order']);
        $this->assertEquals('', $data['pages'][0]['content']);
        $this->assertEquals(0, $data['pages'][0]['has_content']);
        $this->assertEquals(0, $data['pages'][0]['parent_id']);

        wp_delete_post($post_id);
    }

    /**
     * Calling export_pages() with pages that have modified content returns data
     */
    function test_export_pages_with_modified_content_returns_data()
    {
        $post_id = $this->factory->post->create(
            array(
                'post_type' => 'page',
                'post_title' => 'Test Page',
                'post_content' => 'This is the original content'
            )
        );

        update_option('wmpack_page_'.$post_id, 'This is the modified content');

        $export = new WMobilePack_Export();
        $data = json_decode($export->export_pages(), true);

        $this->assertArrayHasKey('pages', $data);
        $this->assertEquals(1, count($data['pages']));
        $this->assertEquals($post_id, $data['pages'][0]['id']);
        $this->assertEquals('Test Page', $data['pages'][0]['title']);
        $this->assertEquals(1, $data['pages'][0]['order']);
        $this->assertEquals('', $data['pages'][0]['content']);
        $this->assertEquals(1, $data['pages'][0]['has_content']);
        $this->assertEquals(0, $data['pages'][0]['parent_id']);

        wp_delete_post($post_id);
    }
}