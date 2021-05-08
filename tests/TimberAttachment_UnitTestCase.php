<?php

	class TimberAttachment_UnitTestCase extends Timber_UnitTestCase {

		var $_files;

		protected function addFile( $file ) {
			$this->_files[] = $file;
		}

		function setUp(){
			parent::setUp();
			$this->_files = array();
		}

		function tearDown() {
			parent::tearDown();
			if (isset($this->_files) && is_array($this->_files)) {
				foreach($this->_files as $file) {
					if (file_exists($file)) {
						unlink($file);
					}
				}
				$this->_files = array();
			}
		}

		/* ----------------
		 * Helper functions
		 ---------------- */

	 	static function replace_attachment( $old_id, $new_id ) {
			$uploadDir = wp_get_upload_dir();
			$newFile = $uploadDir['basedir'].'/'.get_post_meta($new_id, '_wp_attached_file', true);
			$oldFile = $uploadDir['basedir'].'/'.get_post_meta($old_id, '_wp_attached_file', true);
			if (!file_exists(dirname($oldFile)))
				mkdir(dirname($oldFile), 0777, true);
			copy($newFile, $oldFile);
			$meta = wp_generate_attachment_metadata($old_id, $oldFile);
			wp_update_attachment_metadata($old_id, $meta);
			wp_delete_post($new_id, true);
	 	}

		static function copyTestAttachment( $img = 'arch.jpg', $dest_name = null ) {
			$upload_dir = wp_get_upload_dir();
			if ( is_null($dest_name) ) {
				$dest_name = $img;
			}
			$destination = $upload_dir['path'].'/'.$dest_name;
			copy( __DIR__.'/assets/'.$img, $destination );
			return $destination;
		}

		static function getTestAttachmentURL( $img = 'arch.jpg', $relative = false) {
			$upload_dir = wp_get_upload_dir();
			$result = $upload_dir['url'].'/'.$img;
			if ( $relative ) {
				$result = str_replace(home_url(), '', $result);
			}
			return $result;
		}

		public static function is_connected() {
			$connected = @fsockopen( "www.google.com", 80, $errno, $errstr, 3 );
			if ( $connected ) {
				$is_conn = true; //action when connected
				fclose( $connected );
			} else {
				$is_conn = false; //action in connection failure
			}
			return $is_conn;
		}

		function add_lang_to_home( $url, $path, $orig_scheme, $blog_id ){
			return "$url?lang=en";
		}

		public static function get_attachment( $pid = 0, $file = 'arch.jpg' ) {
			$filename = self::copyTestAttachment( $file );
			$filetype = wp_check_filetype( basename( $filename ), null );
			$attachment = array('post_title' => 'The Arch', 'post_content' => '', 'post_mime_type' => $filetype['type']);
			$iid = wp_insert_attachment( $attachment, $filename, $pid );
			return $iid;
		}

		public static function get_timber_attachment_object($file = 'cropper.png') {
			$iid = self::get_attachment(0, $file);
			return Timber::get_post( $iid );
		}

	}
