<?php

namespace Timber;

use WP_Post;

use Timber\Factory\PostFactory;

/**
 * Class Attachment
 *
 * Objects of this class represent WordPress attachments. This is the basis that `Timber\Image`
 * objects build upon.
 *
 * @api
 * @since 2.0.0
 */
class Attachment extends Post implements CoreInterface {
	/**
	 * Representation.
	 *
	 * @var string What does this class represent in WordPress terms?
	 */
	public static $representation = 'attachment';

	/**
	 * Object type.
	 *
	 * @var string What the object represents in WordPress terms.
	 */
	public $object_type = 'attachment';

	/**
	 * File.
	 *
	 * @api
	 * @var mixed
	 */
	public $file;

	/**
	 * File location.
	 *
	 * @api
	 * @var string The absolute path to the attachmend file in the filesystem
	 *             (Example: `/var/www/htdocs/wp-content/uploads/2015/08/my-pic.jpg`)
	 */
	public $file_loc;

	/**
	 * Raw file size.
	 *
	 * @api
	 * @since 2.0.0
	 * @var int Raw file size in bytes.
	 */
	public $file_size_raw = null;

	/**
	 * Formatted file size.
	 *
	 * @api
	 * @since 2.0.0
	 * @var null|string File size string.
	 */
	public $file_size = null;

	/**
	 * File extension.
	 *
	 * @api
	 * @since 2.0.0
	 * @var null|string A file extension.
	 */
	public $file_extension = null;

	/**
	 * Absolute URL.
	 *
	 * @var string The absolute URL to the attachment.
	 */
	public $abs_url;

	/**
	 * Attachment ID.
	 *
	 * @api
	 * @var integer The attachment ID.
	 */
	public $id;

	/**
	 * Attached file.
	 *
	 * @var array The file as stored in the WordPress database.
	 */
	protected $_wp_attached_file;

	/**
	 * File types.
	 *
	 * @var array An array of supported relative file types.
	 */
	private $image_file_types = array(
		'jpg',
		'jpeg',
		'png',
		'svg',
		'bmp',
		'ico',
		'gif',
		'tiff',
		'pdf',
	);

	/**
	 * Caption text.
	 *
	 * @api
	 * @var string The caption that is stored as post_excerpt in the posts table in the database.
	 */
	public $caption;

	/**
	 * Gets the src for an attachment.
	 *
	 * @api
	 *
	 * @return string The src of the attachment.
	 */
	public function __toString() {
		return $this->src();
	}

	/**
	 * Inits the object with an absolute path.
	 *
	 * @internal
	 *
	 * @param string $file_path An absolute path to a file.
	 */
	protected function init_with_file_path( $file_path ) {
		$url = URLHelper::file_system_to_url( $file_path );

		$this->abs_url  = $url;
		$this->file_loc = $file_path;
		$this->file     = $file_path;
	}

	/**
	 * Inits the object with a relative path.
	 *
	 * @internal
	 *
	 * @param string $relative_path A relative path to a file.
	 */
	protected function init_with_relative_path( $relative_path ) {
		$file_path = URLHelper::get_full_path( $relative_path );

		$this->abs_url  = home_url( $relative_path );
		$this->file_loc = $file_path;
		$this->file     = $file_path;
	}

	/**
	 * Inits the object with an URL.
	 *
	 * @internal
	 *
	 * @param string $url An URL on the same host.
	 */
	protected function init_with_url( $url ) {
		$this->abs_url = $url;

		if ( URLHelper::is_local( $url ) ) {
			$this->file     = URLHelper::remove_double_slashes(
				ABSPATH . URLHelper::get_rel_url( $url )
			);
			$this->file_loc = URLHelper::remove_double_slashes(
				ABSPATH . URLHelper::get_rel_url( $url )
			);
		}
	}

	/**
	 * Gets the attachment information.
	 *
	 * @internal
	 *
	 * @param int $attachment_id The ID number of the image in the WP database.
	 * @return array Attachment info as an array or ID
	 */
	protected function get_info( WP_Post $wp_post ) {
		$post_data   = get_object_vars( parent::get_info( $wp_post ) );
		$image_info  = wp_get_attachment_metadata( $wp_post->ID ) ?: [];
		$meta_values = $this->raw_meta();

		$data = array_merge( $post_data, $image_info, $meta_values );

		$basedir = wp_get_upload_dir()['basedir'];

		if ( isset( $data['file'] ) ) {
			$data['file_loc'] = $basedir . DIRECTORY_SEPARATOR . $data['file'];
		} elseif ( isset( $data['_wp_attached_file'] ) ) {
			$data['file']     = $data['_wp_attached_file'];
			$data['file_loc'] = $basedir . DIRECTORY_SEPARATOR . $data['file'];
		}

		return $data;
	}

	/**
	 * Secures an URL based on the current environment.
	 *
	 * @param  string $url The URL to evaluate.
	 *
	 * @return string An URL with or without http/https, depending on what’s appropriate for server.
	 */
	protected function maybe_secure_url( $url ) {
		if ( is_ssl() && strpos( $url, 'https' ) !== 0 && strpos( $url, 'http' ) === 0 ) {
			$url = 'https' . substr( $url, strlen( 'http' ) );
		}

		return $url;
	}

	/**
	 * Gets the link to an attachment.
	 *
	 * This returns a link to an attachment’s page, but not the link to the image src itself.
	 *
	 * @api
	 * @example
	 * ```twig
	 * <a href="{{ image.link }}"><img src="{{ image.src }} "></a>
	 * ```
	 * ```html
	 * <a href="http://example.org/my-cool-picture">
	 *     <img src="http://example.org/wp-content/uploads/2015/whatever.jpg"/>
	 * </a>
	 * ```
	 *
	 * @return string The URL of the attachment.
	 */
	public function link() {
		if ( strlen( $this->abs_url ) ) {
			return $this->abs_url;
		}

		return get_permalink( $this->ID );
	}

	/**
	 * Gets the relative path to an attachment.
	 *
	 * @api
	 * @example
	 * ```twig
	 * <img src="{{ image.path }}" />
	 * ```
	 * ```html
	 * <img src="/wp-content/uploads/2015/08/pic.jpg" />
	 * ```
	 *
	 * @return string The relative path to an attachment.
	 */
	public function path() {
		return URLHelper::get_rel_path( $this->src() );
	}

	/**
	 * Gets the source URL for an attachment.
	 *
	 * @api
	 * @example
	 * ```twig
	 * <a href="{{ get_attachment(post.meta('job_pdf')).src }}" download>
	 * ```
	 * ```html
	 * <a href="http://example.org/wp-content/uploads/2015/08/job-ad-5noe2304i.pdf" download>
	 * ```
	 *
	 * @return bool|string
	 */
	public function src() {
		if ( isset( $this->abs_url ) ) {
			return $this->maybe_secure_url( $this->abs_url );
		}

		return wp_get_attachment_url( $this->ID );
	}

	/**
	 * Gets filesize in a human readable format.
	 *
	 * This can be useful if you want to display the human readable filesize for a file. It’s
	 * easier to read «16 KB» than «16555 bytes» or «1 MB» than «1048576 bytes».
	 *
	 * @api
	 * @since 2.0.0
	 * @example
	 *
	 * Use filesize information in a link that downloads a file:
	 *
	 * ```twig
	 * <a class="download" href="{{ attachment.src }}" download="{{ attachment.title }}">
	 *     <span class="download-title">{{ attachment.title }}</span>
	 *     <span class="download-info">(Download, {{ attachment.size }})</span>
	 * </a>
	 * ```
	 *
	 * @return mixed|null The filesize string in a human readable format.
	 */
	public function size() {
		if ( ! $this->file_size ) {
			$formatted_size  = size_format( $this->size_raw() );
			$this->file_size = str_replace( ' ', '&nbsp;', $formatted_size );
		}

		return $this->file_size;
	}

	/**
	 * Gets filesize in bytes.
	 *
	 * @api
	 * @since 2.0.0
	 * @example
	 *
	 * ```twig
	 * <table>
	 *     {% for attachment in Attachment(attachment_ids) %}
	 *         <tr>
	 *             <td>{{ attachment.title }}</td>
	 *             <td>{{ attachment.extension }}</td>
	 *             <td>{{ attachment.size_raw }} bytes</td>
	 *         </tr>
	 *     {% endfor %}
	 * </table>
	 * ```
	 *
	 * @return mixed|null The filesize string in bytes, or false if the filesize can’t be read.
	 */
	public function size_raw() {
		if ( ! $this->file_size_raw ) {
			$this->file_size_raw = filesize( $this->file_loc );
		}

		return $this->file_size_raw;
	}

	/**
	 * Gets the extension of the attached file.
	 *
	 * @api
	 * @since 2.0.0
	 * @example
	 *
	 * Use extension information in a link that downloads a file:
	 *
	 * ```twig
	 * <a class="download" href="{{ attachment.src }}" download="{{ attachment.title }}">
	 *     <span class="download-title">{{ attachment.title }}</span>
	 *     <span class="download-info">
	 *         (Download {{ attachment.extension|upper }}, {{ attachment.size }})
	 *     </span>
	 * </a>
	 * ```
	 *
	 * @return null|string An uppercase extension string.
	 */
	public function extension() {
		if ( ! $this->file_extension ) {
			$file_info = wp_check_filetype( $this->file );

			if ( ! empty( $file_info['ext'] ) ) {
				$this->file_extension = strtoupper( $file_info['ext'] );
			}
		}

		return $this->file_extension;
	}

	/**
	 * Gets the parent object.
	 *
	 * The parent object of an attachment is a post it is assigned to.
	 *
	 * @api
	 * @example
	 * ```twig
	 * This image is assigned to {{ image.parent.title }}
	 * ```
	 *
	 * @return false|\Timber\Post Parent object as a `Timber\Post`. Returns `false` if no parent
	 *                            object is defined.
	 */
	public function parent() {
		if ( ! $this->post_parent ) {
			return false;
		}

		$factory = new PostFactory();

		return $factory->from( $this->post_parent );
	}

	/**
	 * Get a PHP array with pathinfo() info from the file
	 *
	 * @deprecated 2.0.0, use Attachment::pathinfo() instead
	 * @return array
	 */
	public function get_pathinfo() {
		Helper::deprecated(
			"{{ image.get_pathinfo }}",
			"{{ image.pathinfo }}",
			'2.0.0'
		);
		return PathHelper::pathinfo($this->file);
	}

	/**
	 * Get a PHP array with pathinfo() info from the file
	 *
	 * @return array
	 */
	public function pathinfo() {
		return PathHelper::pathinfo($this->file);
	}
}
