<?php /** @noinspection PhpUnused */
/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */
/** @noinspection PhpComposerExtensionStubsInspection */

/**
 * Recursive directory creation based on full path.
 *
 * Will attempt to set permissions on folders.
 *
 * @since 2.0.1
 *
 * @param string $target Full path to attempt to create.
 * @return bool Whether the path was created. True if path already exists.
 */
function wp_mkdir_p( $target ) {
    $wrapper = null;

    // Strip the protocol.
    if ( wp_is_stream( $target ) ) {
        list( $wrapper, $target ) = explode( '://', $target, 2 );
    }

    // From php.net/mkdir user contributed notes.
    $target = str_replace( '//', '/', $target );

    // Put the wrapper back on the target.
    if ( null !== $wrapper ) {
        $target = $wrapper . '://' . $target;
    }

    /*
	 * Safe mode fails with a trailing slash under certain PHP versions.
	 * Use rtrim() instead of untrailingslashit to avoid formatting.php dependency.
	 */
    $target = rtrim( $target, '/' );
    if ( empty( $target ) ) {
        $target = '/';
    }

    if ( file_exists( $target ) ) {
        return @is_dir( $target );
    }

    // Do not allow path traversals.
    if ( false !== strpos( $target, '../' ) || false !== strpos( $target, '..' . DIRECTORY_SEPARATOR ) ) {
        return false;
    }

    // We need to find the permissions of the parent folder that exists and inherit that.
    $target_parent = dirname( $target );
    while ( '.' !== $target_parent && ! is_dir( $target_parent ) && dirname( $target_parent ) !== $target_parent ) {
        $target_parent = dirname( $target_parent );
    }

    // Get the permission bits.
    $stat = @stat( $target_parent );
    if ( $stat ) {
        $dir_perms = $stat['mode'] & 0007777;
    } else {
        $dir_perms = 0777;
    }

    if ( @mkdir( $target, $dir_perms, true ) ) {

        /*
		 * If a umask is set that modifies $dir_perms, we'll have to re-set
		 * the $dir_perms correctly with chmod()
		 */
        if ( ( $dir_perms & ~umask() ) != $dir_perms ) {
            $folder_parts = explode( '/', substr( $target, strlen( $target_parent ) + 1 ) );
            for ( $i = 1, $c = count( $folder_parts ); $i <= $c; $i++ ) {
                chmod( $target_parent . '/' . implode( '/', array_slice( $folder_parts, 0, $i ) ), $dir_perms );
            }
        }

        return true;
    }

    return false;
}

/**
 * Test if a given filesystem path is absolute.
 *
 * For example, '/foo/bar', or 'c:\windows'.
 *
 * @since 2.5.0
 *
 * @param string $path File path.
 * @return bool True if path is absolute, false is not absolute.
 */
function path_is_absolute( $path ) {
    /*
	 * Check to see if the path is a stream and check to see if its an actual
	 * path or file as realpath() does not support stream wrappers.
	 */
    if ( wp_is_stream( $path ) && ( is_dir( $path ) || is_file( $path ) ) ) {
        return true;
    }

    /*
	 * This is definitive if true but fails if $path does not exist or contains
	 * a symbolic link.
	 */
    if ( realpath( $path ) == $path ) {
        return true;
    }

    if ( strlen( $path ) == 0 || '.' === $path[0] ) {
        return false;
    }

    // Windows allows absolute paths like this.
    if ( preg_match( '#^[a-zA-Z]:\\\\#', $path ) ) {
        return true;
    }

    // A path starting with / or \ is absolute; anything else is relative.
    return ( '/' === $path[0] || '\\' === $path[0] );
}

/**
 * Join two filesystem paths together.
 *
 * For example, 'give me $path relative to $base'. If the $path is absolute,
 * then it the full path is returned.
 *
 * @since 2.5.0
 *
 * @param string $base Base path.
 * @param string $path Path relative to $base.
 * @return string The path with the base or absolute path.
 */
function path_join( $base, $path ) {
    if ( path_is_absolute( $path ) ) {
        return $path;
    }

    return rtrim( $base, '/' ) . '/' . ltrim( $path, '/' );
}

/**
 * Normalize a filesystem path.
 *
 * On windows systems, replaces backslashes with forward slashes
 * and forces upper-case drive letters.
 * Allows for two leading slashes for Windows network shares, but
 * ensures that all other duplicate slashes are reduced to a single.
 *
 * @since 3.9.0
 * @since 4.4.0 Ensures upper-case drive letters on Windows systems.
 * @since 4.5.0 Allows for Windows network shares.
 * @since 4.9.7 Allows for PHP file wrappers.
 *
 * @param string $path Path to normalize.
 * @return string Normalized path.
 */
function wp_normalize_path( $path ) {
    $wrapper = '';

    if ( wp_is_stream( $path ) ) {
        list( $wrapper, $path ) = explode( '://', $path, 2 );

        $wrapper .= '://';
    }

    // Standardise all paths to use '/'.
    $path = str_replace( '\\', '/', $path );

    // Replace multiple slashes down to a singular, allowing for network shares having two slashes.
    $path = preg_replace( '|(?<=.)/+|', '/', $path );

    // Windows paths should uppercase the drive letter.
    if ( ':' === substr( $path, 1, 1 ) ) {
        $path = ucfirst( $path );
    }

    return $wrapper . $path;
}

/**
 * Determine a writable directory for temporary files.
 *
 * Function's preference is the return value of sys_get_temp_dir(),
 * followed by your PHP temporary upload directory, followed by WP_CONTENT_DIR,
 * before finally defaulting to /tmp/
 *
 * In the event that this function does not find a writable location,
 * It may be overridden by the WP_TEMP_DIR constant in your wp-config.php file.
 *
 * @since 2.5.0
 *
 * @return string Writable temporary directory.
 */
function get_temp_dir() {
    static $temp = '';
    if ( defined( 'WP_TEMP_DIR' ) ) {
        return trailingslashit( WP_TEMP_DIR );
    }

    if ( $temp ) {
        return trailingslashit( $temp );
    }

    if ( function_exists( 'sys_get_temp_dir' ) ) {
        $temp = sys_get_temp_dir();
        if ( @is_dir( $temp ) && wp_is_writable( $temp ) ) {
            return trailingslashit( $temp );
        }
    }

    $temp = ini_get( 'upload_tmp_dir' );
    if ( @is_dir( $temp ) && wp_is_writable( $temp ) ) {
        return trailingslashit( $temp );
    }

    $temp = WP_CONTENT_DIR . '/';
    if ( is_dir( $temp ) && wp_is_writable( $temp ) ) {
        return $temp;
    }

    return '/tmp/';
}

/**
 * Determine if a directory is writable.
 *
 * This function is used to work around certain ACL issues in PHP primarily
 * affecting Windows Servers.
 *
 * @since 3.6.0
 *
 * @see win_is_writable()
 *
 * @param string $path Path to check for write-ability.
 * @return bool Whether the path is writable.
 */
function wp_is_writable( $path ) {
    if ( 'WIN' === strtoupper( substr( PHP_OS, 0, 3 ) ) ) {
        return win_is_writable( $path );
    } else {
        return @is_writable( $path );
    }
}

/**
 * Workaround for Windows bug in is_writable() function
 *
 * PHP has issues with Windows ACL's for determine if a
 * directory is writable or not, this works around them by
 * checking the ability to open files rather than relying
 * upon PHP to interprate the OS ACL.
 *
 * @since 2.8.0
 *
 * @see https://bugs.php.net/bug.php?id=27609
 * @see https://bugs.php.net/bug.php?id=30931
 *
 * @param string $path Windows path to check for write-ability.
 * @return bool Whether the path is writable.
 */
function win_is_writable( $path ) {
    if ( '/' === $path[ strlen( $path ) - 1 ] ) {
        // If it looks like a directory, check a random file within the directory.
        return win_is_writable( $path . uniqid( mt_rand() ) . '.tmp' );
    } elseif ( is_dir( $path ) ) {
        // If it's a directory (and not a file), check a random file within the directory.
        return win_is_writable( $path . '/' . uniqid( mt_rand() ) . '.tmp' );
    }

    // Check tmp file for read/write capabilities.
    $should_delete_tmp_file = ! file_exists( $path );

    $f = @fopen( $path, 'a' );
    if ( false === $f ) {
        return false;
    }
    fclose( $f );

    if ( $should_delete_tmp_file ) {
        unlink( $path );
    }

    return true;
}

/**
 * Retrieves uploads directory information.
 *
 * Same as wp_upload_dir() but "light weight" as it doesn't attempt to create the uploads directory.
 * Intended for use in themes, when only 'basedir' and 'baseurl' are needed, generally in all cases
 * when not uploading files.
 *
 * @since 4.5.0
 *
 * @see wp_upload_dir()
 *
 * @return array See wp_upload_dir() for description.
 */
function wp_get_upload_dir() {
    return wp_upload_dir( null, false );
}

/**
 * Returns an array containing the current upload directory's path and URL.
 *
 * Checks the 'upload_path' option, which should be from the web root folder,
 * and if it isn't empty it will be used. If it is empty, then the path will be
 * 'WP_CONTENT_DIR/uploads'. If the 'UPLOADS' constant is defined, then it will
 * override the 'upload_path' option and 'WP_CONTENT_DIR/uploads' path.
 *
 * The upload URL path is set either by the 'upload_url_path' option or by using
 * the 'WP_CONTENT_URL' constant and appending '/uploads' to the path.
 *
 * If the 'uploads_use_yearmonth_folders' is set to true (checkbox if checked in
 * the administration settings panel), then the time will be used. The format
 * will be year first and then month.
 *
 * If the path couldn't be created, then an error will be returned with the key
 * 'error' containing the error message. The error suggests that the parent
 * directory is not writable by the server.
 *
 * @since 2.0.0
 * @uses _wp_upload_dir()
 *
 * @param string $time Optional. Time formatted in 'yyyy/mm'. Default null.
 * @param bool   $create_dir Optional. Whether to check and create the uploads directory.
 *                           Default true for backward compatibility.
 * @param bool   $refresh_cache Optional. Whether to refresh the cache. Default false.
 * @return array {
 *     Array of information about the upload directory.
 *
 *     @type string       $path    Base directory and subdirectory or full path to upload directory.
 *     @type string       $url     Base URL and subdirectory or absolute URL to upload directory.
 *     @type string       $subdir  Subdirectory if uploads use year/month folders option is on.
 *     @type string       $basedir Path without subdir.
 *     @type string       $baseurl URL path without subdir.
 *     @type string|false $error   False or error message.
 * }
 */
function wp_upload_dir( $time = null, $create_dir = true, $refresh_cache = false ) {
    static $cache = array(), $tested_paths = array();

    /** @noinspection PhpUnnecessaryStringCastInspection */
    $key = sprintf( '%d-%s', get_current_blog_id(), (string) $time );

    if ( $refresh_cache || empty( $cache[ $key ] ) ) {
        $cache[ $key ] = _wp_upload_dir( $time );
    }

    /**
     * Filters the uploads directory data.
     *
     * @since 2.0.0
     *
     * @param array $uploads {
     *     Array of information about the upload directory.
     *
     *     @type string       $path    Base directory and subdirectory or full path to upload directory.
     *     @type string       $url     Base URL and subdirectory or absolute URL to upload directory.
     *     @type string       $subdir  Subdirectory if uploads use year/month folders option is on.
     *     @type string       $basedir Path without subdir.
     *     @type string       $baseurl URL path without subdir.
     *     @type string|false $error   False or error message.
     * }
     */
    $uploads = apply_filters( 'upload_dir', $cache[ $key ] );

    if ( $create_dir ) {
        $path = $uploads['path'];

        if ( array_key_exists( $path, $tested_paths ) ) {
            $uploads['error'] = $tested_paths[ $path ];
        } else {
            if ( ! wp_mkdir_p( $path ) ) {
                if ( 0 === strpos( $uploads['basedir'], ABSPATH ) ) {
                    $error_path = str_replace( ABSPATH, '', $uploads['basedir'] ) . $uploads['subdir'];
                } else {
                    $error_path = wp_basename( $uploads['basedir'] ) . $uploads['subdir'];
                }

                $uploads['error'] = sprintf(
                /* translators: %s: Directory path. */
                  __( 'Unable to create directory %s. Is its parent directory writable by the server?' ),
                  esc_html( $error_path )
                );
            }

            $tested_paths[ $path ] = $uploads['error'];
        }
    }

    return $uploads;
}

/**
 * A non-filtered, non-cached version of wp_upload_dir() that doesn't check the path.
 *
 * @since 4.5.0
 * @access private
 *
 * @param string $time Optional. Time formatted in 'yyyy/mm'. Default null.
 * @return array See wp_upload_dir()
 */
function _wp_upload_dir( $time = null ) {
    $siteurl     = get_option( 'siteurl' );
    $upload_path = trim( get_option( 'upload_path' ) );

    if ( empty( $upload_path ) || 'wp-content/uploads' === $upload_path ) {
        $dir = WP_CONTENT_DIR . '/uploads';
    } elseif ( 0 !== strpos( $upload_path, ABSPATH ) ) {
        // $dir is absolute, $upload_path is (maybe) relative to ABSPATH.
        $dir = path_join( ABSPATH, $upload_path );
    } else {
        $dir = $upload_path;
    }

    $url = get_option( 'upload_url_path' );
    if ( ! $url ) {
        if ( empty( $upload_path ) || ( 'wp-content/uploads' === $upload_path ) || ( $upload_path == $dir ) ) {
            $url = WP_CONTENT_URL . '/uploads';
        } else {
            $url = trailingslashit( $siteurl ) . $upload_path;
        }
    }

    /*
	 * Honor the value of UPLOADS. This happens as long as ms-files rewriting is disabled.
	 * We also sometimes obey UPLOADS when rewriting is enabled -- see the next block.
	 */
    if ( defined( 'UPLOADS' ) && ! ( is_multisite() && get_site_option( 'ms_files_rewriting' ) ) ) {
        $dir = ABSPATH . UPLOADS;
        $url = trailingslashit( $siteurl ) . UPLOADS;
    }

    // If multisite (and if not the main site in a post-MU network).
    if ( is_multisite() && ! ( is_main_network() && is_main_site() && defined( 'MULTISITE' ) ) ) {

        if ( ! get_site_option( 'ms_files_rewriting' ) ) {
            /*
			 * If ms-files rewriting is disabled (networks created post-3.5), it is fairly
			 * straightforward: Append sites/%d if we're not on the main site (for post-MU
			 * networks). (The extra directory prevents a four-digit ID from conflicting with
			 * a year-based directory for the main site. But if a MU-era network has disabled
			 * ms-files rewriting manually, they don't need the extra directory, as they never
			 * had wp-content/uploads for the main site.)
			 */

            if ( defined( 'MULTISITE' ) ) {
                $ms_dir = '/sites/' . get_current_blog_id();
            } else {
                $ms_dir = '/' . get_current_blog_id();
            }

            $dir .= $ms_dir;
            $url .= $ms_dir;

        } elseif ( defined( 'UPLOADS' ) && ! ms_is_switched() ) {
            /*
			 * Handle the old-form ms-files.php rewriting if the network still has that enabled.
			 * When ms-files rewriting is enabled, then we only listen to UPLOADS when:
			 * 1) We are not on the main site in a post-MU network, as wp-content/uploads is used
			 *    there, and
			 * 2) We are not switched, as ms_upload_constants() hardcodes these constants to reflect
			 *    the original blog ID.
			 *
			 * Rather than UPLOADS, we actually use BLOGUPLOADDIR if it is set, as it is absolute.
			 * (And it will be set, see ms_upload_constants().) Otherwise, UPLOADS can be used, as
			 * as it is relative to ABSPATH. For the final piece: when UPLOADS is used with ms-files
			 * rewriting in multisite, the resulting URL is /files. (#WP22702 for background.)
			 */

            if ( defined( 'BLOGUPLOADDIR' ) ) {
                $dir = untrailingslashit( BLOGUPLOADDIR );
            } else {
                $dir = ABSPATH . UPLOADS;
            }
            $url = trailingslashit( $siteurl ) . 'files';
        }
    }

    $basedir = $dir;
    $baseurl = $url;

    $subdir = '';
    if ( get_option( 'uploads_use_yearmonth_folders' ) ) {
        // Generate the yearly and monthly directories.
        if ( ! $time ) {
            $time = current_time( 'mysql' );
        }
        $y      = substr( $time, 0, 4 );
        $m      = substr( $time, 5, 2 );
        $subdir = "/$y/$m";
    }

    $dir .= $subdir;
    $url .= $subdir;

    return array(
      'path'    => $dir,
      'url'     => $url,
      'subdir'  => $subdir,
      'basedir' => $basedir,
      'baseurl' => $baseurl,
      'error'   => false,
    );
}

/**
 * Get a filename that is sanitized and unique for the given directory.
 *
 * If the filename is not unique, then a number will be added to the filename
 * before the extension, and will continue adding numbers until the filename
 * is unique.
 *
 * The callback function allows the caller to use their own method to create
 * unique file names. If defined, the callback should take three arguments:
 * - directory, base filename, and extension - and return a unique filename.
 *
 * @since 2.5.0
 *
 * @param string   $dir                      Directory.
 * @param string   $filename                 File name.
 * @param callable $unique_filename_callback Callback. Default null.
 * @return string New filename, if given wasn't unique.
 */
function wp_unique_filename( $dir, $filename, $unique_filename_callback = null ) {
    // Sanitize the file name before we begin processing.
    $filename = sanitize_file_name( $filename );
    /** @noinspection PhpUnusedLocalVariableInspection */
    $ext2     = null;

    // Initialize vars used in the wp_unique_filename filter.
    $number        = '';
    $alt_filenames = array();

    // Separate the filename into a name and extension.
    $ext  = pathinfo( $filename, PATHINFO_EXTENSION );
    $name = pathinfo( $filename, PATHINFO_BASENAME );

    if ( $ext ) {
        $ext = '.' . $ext;
    }

    // Edge case: if file is named '.ext', treat as an empty name.
    if ( $name === $ext ) {
        $name = '';
    }

    /*
	 * Increment the file number until we have a unique file to save in $dir.
	 * Use callback if supplied.
	 */
    if ( $unique_filename_callback && is_callable( $unique_filename_callback ) ) {
        $filename = call_user_func( $unique_filename_callback, $dir, $name, $ext );
    } else {
        $fname = pathinfo( $filename, PATHINFO_FILENAME );

        // Always append a number to file names that can potentially match image sub-size file names.
        if ( $fname && preg_match( '/-(?:\d+x\d+|scaled|rotated)$/', $fname ) ) {
            $number = 1;

            // At this point the file name may not be unique. This is tested below and the $number is incremented.
            $filename = str_replace( "{$fname}{$ext}", "{$fname}-{$number}{$ext}", $filename );
        }

        /*
		 * Get the mime type. Uploaded files were already checked with wp_check_filetype_and_ext()
		 * in _wp_handle_upload(). Using wp_check_filetype() would be sufficient here.
		 */
        $file_type = wp_check_filetype( $filename );
        $mime_type = $file_type['type'];

        $is_image    = ( ! empty( $mime_type ) && 0 === strpos( $mime_type, 'image/' ) );
        $upload_dir  = wp_get_upload_dir();
        $lc_filename = null;

        $lc_ext = strtolower( $ext );
        $_dir   = trailingslashit( $dir );

        /*
		 * If the extension is uppercase add an alternate file name with lowercase extension.
		 * Both need to be tested for uniqueness as the extension will be changed to lowercase
		 * for better compatibility with different filesystems. Fixes an inconsistency in WP < 2.9
		 * where uppercase extensions were allowed but image sub-sizes were created with
		 * lowercase extensions.
		 */
        if ( $ext && $lc_ext !== $ext ) {
            $lc_filename = preg_replace( '|' . preg_quote( $ext ) . '$|', $lc_ext, $filename );
        }

        /*
		 * Increment the number added to the file name if there are any files in $dir
		 * whose names match one of the possible name variations.
		 */
        while ( file_exists( $_dir . $filename ) || ( $lc_filename && file_exists( $_dir . $lc_filename ) ) ) {
            $new_number = (int) $number + 1;

            if ( $lc_filename ) {
                $lc_filename = str_replace(
                  array( "-{$number}{$lc_ext}", "{$number}{$lc_ext}" ),
                  "-{$new_number}{$lc_ext}",
                  $lc_filename
                );
            }

            if ( '' === "{$number}{$ext}" ) {
                $filename = "{$filename}-{$new_number}";
            } else {
                $filename = str_replace(
                  array( "-{$number}{$ext}", "{$number}{$ext}" ),
                  "-{$new_number}{$ext}",
                  $filename
                );
            }

            $number = $new_number;
        }

        // Change the extension to lowercase if needed.
        if ( $lc_filename ) {
            $filename = $lc_filename;
        }

        /*
		 * Prevent collisions with existing file names that contain dimension-like strings
		 * (whether they are subsizes or originals uploaded prior to #42437).
		 */

        $files = array();
        $count = 10000;

        // The (resized) image files would have name and extension, and will be in the uploads dir.
        if ( $name && $ext && @is_dir( $dir ) && false !== strpos( $dir, $upload_dir['basedir'] ) ) {
            /**
             * Filters the file list used for calculating a unique filename for a newly added file.
             *
             * Returning an array from the filter will effectively short-circuit retrieval
             * from the filesystem and return the passed value instead.
             *
             * @since 5.5.0
             *
             * @param array|null $files    The list of files to use for filename comparisons.
             *                             Default null (to retrieve the list from the filesystem).
             * @param string     $dir      The directory for the new file.
             * @param string     $filename The proposed filename for the new file.
             */
            $files = apply_filters( 'pre_wp_unique_filename_file_list', null, $dir, $filename );

            if ( null === $files ) {
                // List of all files and directories contained in $dir.
                $files = @scandir( $dir );
            }

            if ( ! empty( $files ) ) {
                // Remove "dot" dirs.
                $files = array_diff( $files, array( '.', '..' ) );
            }

            if ( ! empty( $files ) ) {
                $count = count( $files );

                /*
				 * Ensure this never goes into infinite loop as it uses pathinfo() and regex in the check,
				 * but string replacement for the changes.
				 */
                $i = 0;

                while ( $i <= $count && _wp_check_existing_file_names( $filename, $files ) ) {
                    $new_number = (int) $number + 1;

                    // If $ext is uppercase it was replaced with the lowercase version after the previous loop.
                    $filename = str_replace(
                      array( "-{$number}{$lc_ext}", "{$number}{$lc_ext}" ),
                      "-{$new_number}{$lc_ext}",
                      $filename
                    );

                    $number = $new_number;
                    $i++;
                }
            }
        }

        /*
		 * Check if an image will be converted after uploading or some existing image sub-size file names may conflict
		 * when regenerated. If yes, ensure the new file name will be unique and will produce unique sub-sizes.
		 */
        if ( $is_image ) {
            /** This filter is documented in wp-includes/class-wp-image-editor.php */
            $output_formats = apply_filters( 'image_editor_output_format', array(), $_dir . $filename, $mime_type );
            $alt_types      = array();

            if ( ! empty( $output_formats[ $mime_type ] ) ) {
                // The image will be converted to this format/mime type.
                $alt_mime_type = $output_formats[ $mime_type ];

                // Other types of images whose names may conflict if their sub-sizes are regenerated.
                $alt_types   = array_keys( array_intersect( $output_formats, array( $mime_type, $alt_mime_type ) ) );
                $alt_types[] = $alt_mime_type;
            } elseif ( ! empty( $output_formats ) ) {
                $alt_types = array_keys( array_intersect( $output_formats, array( $mime_type ) ) );
            }

            // Remove duplicates and the original mime type. It will be added later if needed.
            $alt_types = array_unique( array_diff( $alt_types, array( $mime_type ) ) );

            foreach ( $alt_types as $alt_type ) {
                $alt_ext = wp_get_default_extension_for_mime_type( $alt_type );

                if ( ! $alt_ext ) {
                    continue;
                }

                $alt_ext      = ".{$alt_ext}";
                $alt_filename = preg_replace( '|' . preg_quote( $lc_ext ) . '$|', $alt_ext, $filename );

                $alt_filenames[ $alt_ext ] = $alt_filename;
            }

            if ( ! empty( $alt_filenames ) ) {
                /*
				 * Add the original filename. It needs to be checked again
				 * together with the alternate filenames when $number is incremented.
				 */
                $alt_filenames[ $lc_ext ] = $filename;

                // Ensure no infinite loop.
                $i = 0;

                while ( $i <= $count && _wp_check_alternate_file_names( $alt_filenames, $_dir, $files ) ) {
                    $new_number = (int) $number + 1;

                    foreach ( $alt_filenames as $alt_ext => $alt_filename ) {
                        $alt_filenames[ $alt_ext ] = str_replace(
                          array( "-{$number}{$alt_ext}", "{$number}{$alt_ext}" ),
                          "-{$new_number}{$alt_ext}",
                          $alt_filename
                        );
                    }

                    /*
					 * Also update the $number in (the output) $filename.
					 * If the extension was uppercase it was already replaced with the lowercase version.
					 */
                    $filename = str_replace(
                      array( "-{$number}{$lc_ext}", "{$number}{$lc_ext}" ),
                      "-{$new_number}{$lc_ext}",
                      $filename
                    );

                    $number = $new_number;
                    $i++;
                }
            }
        }
    }

    /**
     * Filters the result when generating a unique file name.
     *
     * @since 4.5.0
     * @since 5.8.1 The `$alt_filenames` and `$number` parameters were added.
     *
     * @param string        $filename                 Unique file name.
     * @param string        $ext                      File extension. Example: ".png".
     * @param string        $dir                      Directory path.
     * @param callable|null $unique_filename_callback Callback function that generates the unique file name.
     * @param string[]      $alt_filenames            Array of alternate file names that were checked for collisions.
     * @param int|string    $number                   The highest number that was used to make the file name unique
     *                                                or an empty string if unused.
     */
    return apply_filters( 'wp_unique_filename', $filename, $ext, $dir, $unique_filename_callback, $alt_filenames, $number );
}

/**
 * Helper function to test if each of an array of file names could conflict with existing files.
 *
 * @since 5.8.1
 * @access private
 *
 * @param string[] $filenames Array of file names to check.
 * @param string   $dir       The directory containing the files.
 * @param array    $files     An array of existing files in the directory. May be empty.
 * @return bool True if the tested file name could match an existing file, false otherwise.
 */
function _wp_check_alternate_file_names( $filenames, $dir, $files ) {
    foreach ( $filenames as $filename ) {
        if ( file_exists( $dir . $filename ) ) {
            return true;
        }

        if ( ! empty( $files ) && _wp_check_existing_file_names( $filename, $files ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Helper function to check if a file name could match an existing image sub-size file name.
 *
 * @since 5.3.1
 * @access private
 *
 * @param string $filename The file name to check.
 * @param array  $files    An array of existing files in the directory.
 * @return bool True if the tested file name could match an existing file, false otherwise.
 */
function _wp_check_existing_file_names( $filename, $files ) {
    $fname = pathinfo( $filename, PATHINFO_FILENAME );
    $ext   = pathinfo( $filename, PATHINFO_EXTENSION );

    // Edge case, file names like `.ext`.
    if ( empty( $fname ) ) {
        return false;
    }

    if ( $ext ) {
        $ext = ".$ext";
    }

    $regex = '/^' . preg_quote( $fname ) . '-(?:\d+x\d+|scaled|rotated)' . preg_quote( $ext ) . '$/i';

    foreach ( $files as $file ) {
        if ( preg_match( $regex, $file ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Create a file in the upload folder with given content.
 *
 * If there is an error, then the key 'error' will exist with the error message.
 * If success, then the key 'file' will have the unique file path, the 'url' key
 * will have the link to the new file. and the 'error' key will be set to false.
 *
 * This function will not move an uploaded file to the upload folder. It will
 * create a new file with the content in $bits parameter. If you move the upload
 * file, read the content of the uploaded file, and then you can give the
 * filename and content to this function, which will add it to the upload
 * folder.
 *
 * The permissions will be set on the new file automatically by this function.
 *
 * @since 2.0.0
 *
 * @param string      $name       Filename.
 * @param null|string $deprecated Never used. Set to null.
 * @param string      $bits       File content
 * @param string      $time       Optional. Time formatted in 'yyyy/mm'. Default null.
 * @return array {
 *     Information about the newly-uploaded file.
 *
 *     @type string       $file  Filename of the newly-uploaded file.
 *     @type string       $url   URL of the uploaded file.
 *     @type string       $type  File type.
 *     @type string|false $error Error message, if there has been an error.
 * }
 */
function wp_upload_bits( $name, $deprecated, $bits, $time = null ) {
    if ( ! empty( $deprecated ) ) {
        _deprecated_argument( __FUNCTION__, '2.0.0' );
    }

    if ( empty( $name ) ) {
        return array( 'error' => __( 'Empty filename' ) );
    }

    $wp_filetype = wp_check_filetype( $name );
    if ( ! $wp_filetype['ext'] && ! current_user_can( 'unfiltered_upload' ) ) {
        return array( 'error' => __( 'Sorry, you are not allowed to upload this file type.' ) );
    }

    $upload = wp_upload_dir( $time );

    if ( false !== $upload['error'] ) {
        return $upload;
    }

    /**
     * Filters whether to treat the upload bits as an error.
     *
     * Returning a non-array from the filter will effectively short-circuit preparing the upload bits
     * and return that value instead. An error message should be returned as a string.
     *
     * @since 3.0.0
     *
     * @param array|string $upload_bits_error An array of upload bits data, or error message to return.
     */
    $upload_bits_error = apply_filters(
      'wp_upload_bits',
      array(
        'name' => $name,
        'bits' => $bits,
        'time' => $time,
      )
    );
    if ( ! is_array( $upload_bits_error ) ) {
        $upload['error'] = $upload_bits_error;
        return $upload;
    }

    $filename = wp_unique_filename( $upload['path'], $name );

    $new_file = $upload['path'] . "/$filename";
    if ( ! wp_mkdir_p( dirname( $new_file ) ) ) {
        if ( 0 === strpos( $upload['basedir'], ABSPATH ) ) {
            $error_path = str_replace( ABSPATH, '', $upload['basedir'] ) . $upload['subdir'];
        } else {
            $error_path = wp_basename( $upload['basedir'] ) . $upload['subdir'];
        }

        $message = sprintf(
        /* translators: %s: Directory path. */
          __( 'Unable to create directory %s. Is its parent directory writable by the server?' ),
          $error_path
        );
        return array( 'error' => $message );
    }

    $ifp = @fopen( $new_file, 'wb' );
    if ( ! $ifp ) {
        return array(
            /* translators: %s: File name. */
            'error' => sprintf( __( 'Could not write file %s' ), $new_file ),
        );
    }

    fwrite( $ifp, $bits );
    fclose( $ifp );
    clearstatcache();

    // Set correct file permissions.
    $stat  = @ stat( dirname( $new_file ) );
    $perms = $stat['mode'] & 0007777;
    $perms = $perms & 0000666;
    chmod( $new_file, $perms );
    clearstatcache();

    // Compute the URL.
    $url = $upload['url'] . "/$filename";

    if ( is_multisite() ) {
        clean_dirsize_cache( $new_file );
    }

    /** This filter is documented in wp-admin/includes/file.php */
    return apply_filters(
      'wp_handle_upload',
      array(
        'file'  => $new_file,
        'url'   => $url,
        'type'  => $wp_filetype['type'],
        'error' => false,
      ),
      'sideload'
    );
}

/**
 * Retrieve the file type based on the extension name.
 *
 * @since 2.5.0
 *
 * @param string $ext The extension to search.
 * @return string|void The file type, example: audio, video, document, spreadsheet, etc.
 */
function wp_ext2type( $ext ) {
    $ext = strtolower( $ext );

    $ext2type = wp_get_ext_types();
    foreach ( $ext2type as $type => $exts ) {
        if ( in_array( $ext, $exts, true ) ) {
            return $type;
        }
    }
}

/**
 * Returns first matched extension for the mime-type,
 * as mapped from wp_get_mime_types().
 *
 * @since 5.8.1
 *
 * @param string $mime_type
 *
 * @return string|false
 */
function wp_get_default_extension_for_mime_type( $mime_type ) {
    $extensions = explode( '|', array_search( $mime_type, wp_get_mime_types(), true ) );

    if ( empty( $extensions[0] ) ) {
        return false;
    }

    return $extensions[0];
}

/**
 * Retrieve the file type from the file name.
 *
 * You can optionally define the mime array, if needed.
 *
 * @since 2.0.4
 *
 * @param string   $filename File name or path.
 * @param string[] $mimes    Optional. Array of allowed mime types keyed by their file extension regex.
 * @return array {
 *     Values for the extension and mime type.
 *
 *     @type string|false $ext  File extension, or false if the file doesn't match a mime type.
 *     @type string|false $type File mime type, or false if the file doesn't match a mime type.
 * }
 */
function wp_check_filetype( $filename, $mimes = null ) {
    if ( empty( $mimes ) ) {
        $mimes = get_allowed_mime_types();
    }
    $type = false;
    $ext  = false;

    foreach ( $mimes as $ext_preg => $mime_match ) {
        $ext_preg = '!\.(' . $ext_preg . ')$!i';
        if ( preg_match( $ext_preg, $filename, $ext_matches ) ) {
            $type = $mime_match;
            $ext  = $ext_matches[1];
            break;
        }
    }

    return compact( 'ext', 'type' );
}

/**
 * Attempt to determine the real file type of a file.
 *
 * If unable to, the file name extension will be used to determine type.
 *
 * If it's determined that the extension does not match the file's real type,
 * then the "proper_filename" value will be set with a proper filename and extension.
 *
 * Currently this function only supports renaming images validated via wp_get_image_mime().
 *
 * @since 3.0.0
 *
 * @param string   $file     Full path to the file.
 * @param string   $filename The name of the file (may differ from $file due to $file being
 *                           in a tmp directory).
 * @param string[] $mimes    Optional. Array of allowed mime types keyed by their file extension regex.
 * @return array {
 *     Values for the extension, mime type, and corrected filename.
 *
 *     @type string|false $ext             File extension, or false if the file doesn't match a mime type.
 *     @type string|false $type            File mime type, or false if the file doesn't match a mime type.
 *     @type string|false $proper_filename File name with its correct extension, or false if it cannot be determined.
 * }
 */
function wp_check_filetype_and_ext( $file, $filename, $mimes = null ) {
    $proper_filename = false;

    // Do basic extension validation and MIME mapping.
    $wp_filetype = wp_check_filetype( $filename, $mimes );
    $ext         = $wp_filetype['ext'];
    $type        = $wp_filetype['type'];

    // We can't do any further validation without a file to work with.
    if ( ! file_exists( $file ) ) {
        return compact( 'ext', 'type', 'proper_filename' );
    }

    $real_mime = false;

    // Validate image types.
    if ( $type && 0 === strpos( $type, 'image/' ) ) {

        // Attempt to figure out what type of image it actually is.
        $real_mime = wp_get_image_mime( $file );

        if ( $real_mime && $real_mime != $type ) {
            /**
             * Filters the list mapping image mime types to their respective extensions.
             *
             * @since 3.0.0
             *
             * @param array $mime_to_ext Array of image mime types and their matching extensions.
             */
            $mime_to_ext = apply_filters(
              'getimagesize_mimes_to_exts',
              array(
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/bmp'  => 'bmp',
                'image/tiff' => 'tif',
                'image/webp' => 'webp',
              )
            );

            // Replace whatever is after the last period in the filename with the correct extension.
            if ( ! empty( $mime_to_ext[ $real_mime ] ) ) {
                $filename_parts = explode( '.', $filename );
                array_pop( $filename_parts );
                $filename_parts[] = $mime_to_ext[ $real_mime ];
                $new_filename     = implode( '.', $filename_parts );

                if ( $new_filename != $filename ) {
                    $proper_filename = $new_filename; // Mark that it changed.
                }
                // Redefine the extension / MIME.
                $wp_filetype = wp_check_filetype( $new_filename, $mimes );
                $ext         = $wp_filetype['ext'];
                $type        = $wp_filetype['type'];
            } else {
                // Reset $real_mime and try validating again.
                $real_mime = false;
            }
        }
    }

    // Validate files that didn't get validated during previous checks.
    if ( $type && ! $real_mime && extension_loaded( 'fileinfo' ) ) {
        $finfo     = finfo_open( FILEINFO_MIME_TYPE );
        $real_mime = finfo_file( $finfo, $file );
        finfo_close( $finfo );

        // fileinfo often misidentifies obscure files as one of these types.
        $nonspecific_types = array(
          'application/octet-stream',
          'application/encrypted',
          'application/CDFV2-encrypted',
          'application/zip',
        );

        /*
		 * If $real_mime doesn't match the content type we're expecting from the file's extension,
		 * we need to do some additional vetting. Media types and those listed in $nonspecific_types are
		 * allowed some leeway, but anything else must exactly match the real content type.
		 */
        if ( in_array( $real_mime, $nonspecific_types, true ) ) {
            // File is a non-specific binary type. That's ok if it's a type that generally tends to be binary.
            if ( ! in_array( substr( $type, 0, strcspn( $type, '/' ) ), array( 'application', 'video', 'audio' ), true ) ) {
                $type = false;
                $ext  = false;
            }
        } elseif ( 0 === strpos( $real_mime, 'video/' ) || 0 === strpos( $real_mime, 'audio/' ) ) {
            /*
			 * For these types, only the major type must match the real value.
			 * This means that common mismatches are forgiven: application/vnd.apple.numbers is often misidentified as application/zip,
			 * and some media files are commonly named with the wrong extension (.mov instead of .mp4)
			 */
            if ( substr( $real_mime, 0, strcspn( $real_mime, '/' ) ) !== substr( $type, 0, strcspn( $type, '/' ) ) ) {
                $type = false;
                $ext  = false;
            }
        } elseif ( 'text/plain' === $real_mime ) {
            // A few common file types are occasionally detected as text/plain; allow those.
            if ( ! in_array(
              $type,
              array(
                'text/plain',
                'text/csv',
                'application/csv',
                'text/richtext',
                'text/tsv',
                'text/vtt',
              ),
              true
            )
            ) {
                $type = false;
                $ext  = false;
            }
        } elseif ( 'application/csv' === $real_mime ) {
            // Special casing for CSV files.
            if ( ! in_array(
              $type,
              array(
                'text/csv',
                'text/plain',
                'application/csv',
              ),
              true
            )
            ) {
                $type = false;
                $ext  = false;
            }
        } elseif ( 'text/rtf' === $real_mime ) {
            // Special casing for RTF files.
            if ( ! in_array(
              $type,
              array(
                'text/rtf',
                'text/plain',
                'application/rtf',
              ),
              true
            )
            ) {
                $type = false;
                $ext  = false;
            }
        } else {
            if ( $type !== $real_mime ) {
                /*
				 * Everything else including image/* and application/*:
				 * If the real content type doesn't match the file extension, assume it's dangerous.
				 */
                $type = false;
                $ext  = false;
            }
        }
    }

    // The mime type must be allowed.
    if ( $type ) {
        $allowed = get_allowed_mime_types();

        if ( ! in_array( $type, $allowed, true ) ) {
            $type = false;
            $ext  = false;
        }
    }

    /**
     * Filters the "real" file type of the given file.
     *
     * @since 3.0.0
     * @since 5.1.0 The $real_mime parameter was added.
     *
     * @param array        $wp_check_filetype_and_ext {
     *     Values for the extension, mime type, and corrected filename.
     *
     *     @type string|false $ext             File extension, or false if the file doesn't match a mime type.
     *     @type string|false $type            File mime type, or false if the file doesn't match a mime type.
     *     @type string|false $proper_filename File name with its correct extension, or false if it cannot be determined.
     * }
     * @param string       $file                      Full path to the file.
     * @param string       $filename                  The name of the file (may differ from $file due to
     *                                                $file being in a tmp directory).
     * @param string[]     $mimes                     Array of mime types keyed by their file extension regex.
     * @param string|false $real_mime                 The actual mime type or false if the type cannot be determined.
     */
    return apply_filters( 'wp_check_filetype_and_ext', compact( 'ext', 'type', 'proper_filename' ), $file, $filename, $mimes, $real_mime );
}

/**
 * Returns the real mime type of an image file.
 *
 * This depends on exif_imagetype() or getimagesize() to determine real mime types.
 *
 * @since 4.7.1
 * @since 5.8.0 Added support for WebP images.
 *
 * @param string $file Full path to the file.
 * @return string|false The actual mime type or false if the type cannot be determined.
 */
function wp_get_image_mime( $file ) {
    /*
	 * Use exif_imagetype() to check the mimetype if available or fall back to
	 * getimagesize() if exif isn't avaialbe. If either function throws an Exception
	 * we assume the file could not be validated.
	 */
    try {
        if ( is_callable( 'exif_imagetype' ) ) {
            $imagetype = exif_imagetype( $file );
            $mime      = ( $imagetype ) ? image_type_to_mime_type( $imagetype ) : false;
        } elseif ( function_exists( 'getimagesize' ) ) {
            // Don't silence errors when in debug mode, unless running unit tests.
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG
              && ! defined( 'WP_RUN_CORE_TESTS' )
            ) {
                // Not using wp_getimagesize() here to avoid an infinite loop.
                $imagesize = getimagesize( $file );
            } else {
                // phpcs:ignore WordPress.PHP.NoSilencedErrors
                $imagesize = @getimagesize( $file );
            }

            $mime = ( isset( $imagesize['mime'] ) ) ? $imagesize['mime'] : false;
        } else {
            $mime = false;
        }

        if ( false !== $mime ) {
            return $mime;
        }

        $handle = fopen( $file, 'rb' );
        if ( false === $handle ) {
            return false;
        }

        $magic = fread( $handle, 12 );
        if ( false === $magic ) {
            return false;
        }

        /*
		 * Add WebP fallback detection when image library doesn't support WebP.
		 * Note: detection values come from LibWebP, see
		 * https://github.com/webmproject/libwebp/blob/master/imageio/image_dec.c#L30
		 */
        $magic = bin2hex( $magic );
        if (
          // RIFF.
          ( 0 === strpos( $magic, '52494646' ) ) &&
          // WEBP.
          ( 16 === strpos( $magic, '57454250' ) )
        ) {
            $mime = 'image/webp';
        }

        fclose( $handle );
    } catch ( Exception $e ) {
        $mime = false;
    }

    return $mime;
}

/**
 * Retrieve list of mime types and file extensions.
 *
 * @since 3.5.0
 * @since 4.2.0 Support was added for GIMP (.xcf) files.
 * @since 4.9.2 Support was added for Flac (.flac) files.
 * @since 4.9.6 Support was added for AAC (.aac) files.
 *
 * @return string[] Array of mime types keyed by the file extension regex corresponding to those types.
 */
function wp_get_mime_types() {
    /**
     * Filters the list of mime types and file extensions.
     *
     * This filter should be used to add, not remove, mime types. To remove
     * mime types, use the {@see 'upload_mimes'} filter.
     *
     * @since 3.5.0
     *
     * @param string[] $wp_get_mime_types Mime types keyed by the file extension regex
     *                                 corresponding to those types.
     */
    return apply_filters(
      'mime_types',
      array(
          // Image formats.
          'jpg|jpeg|jpe'                 => 'image/jpeg',
          'gif'                          => 'image/gif',
          'png'                          => 'image/png',
          'bmp'                          => 'image/bmp',
          'tiff|tif'                     => 'image/tiff',
          'webp'                         => 'image/webp',
          'ico'                          => 'image/x-icon',
          'heic'                         => 'image/heic',
          // Video formats.
          'asf|asx'                      => 'video/x-ms-asf',
          'wmv'                          => 'video/x-ms-wmv',
          'wmx'                          => 'video/x-ms-wmx',
          'wm'                           => 'video/x-ms-wm',
          'avi'                          => 'video/avi',
          'divx'                         => 'video/divx',
          'flv'                          => 'video/x-flv',
          'mov|qt'                       => 'video/quicktime',
          'mpeg|mpg|mpe'                 => 'video/mpeg',
          'mp4|m4v'                      => 'video/mp4',
          'ogv'                          => 'video/ogg',
          'webm'                         => 'video/webm',
          'mkv'                          => 'video/x-matroska',
          '3gp|3gpp'                     => 'video/3gpp',  // Can also be audio.
          '3g2|3gp2'                     => 'video/3gpp2', // Can also be audio.
          // Text formats.
          'txt|asc|c|cc|h|srt'           => 'text/plain',
          'csv'                          => 'text/csv',
          'tsv'                          => 'text/tab-separated-values',
          'ics'                          => 'text/calendar',
          'rtx'                          => 'text/richtext',
          'css'                          => 'text/css',
          'htm|html'                     => 'text/html',
          'vtt'                          => 'text/vtt',
          'dfxp'                         => 'application/ttaf+xml',
          // Audio formats.
          'mp3|m4a|m4b'                  => 'audio/mpeg',
          'aac'                          => 'audio/aac',
          'ra|ram'                       => 'audio/x-realaudio',
          'wav'                          => 'audio/wav',
          'ogg|oga'                      => 'audio/ogg',
          'flac'                         => 'audio/flac',
          'mid|midi'                     => 'audio/midi',
          'wma'                          => 'audio/x-ms-wma',
          'wax'                          => 'audio/x-ms-wax',
          'mka'                          => 'audio/x-matroska',
          // Misc application formats.
          'rtf'                          => 'application/rtf',
          'js'                           => 'application/javascript',
          'pdf'                          => 'application/pdf',
          'swf'                          => 'application/x-shockwave-flash',
          'class'                        => 'application/java',
          'tar'                          => 'application/x-tar',
          'zip'                          => 'application/zip',
          'gz|gzip'                      => 'application/x-gzip',
          'rar'                          => 'application/rar',
          '7z'                           => 'application/x-7z-compressed',
          'exe'                          => 'application/x-msdownload',
          'psd'                          => 'application/octet-stream',
          'xcf'                          => 'application/octet-stream',
          // MS Office formats.
          'doc'                          => 'application/msword',
          'pot|pps|ppt'                  => 'application/vnd.ms-powerpoint',
          'wri'                          => 'application/vnd.ms-write',
          'xla|xls|xlt|xlw'              => 'application/vnd.ms-excel',
          'mdb'                          => 'application/vnd.ms-access',
          'mpp'                          => 'application/vnd.ms-project',
          'docx'                         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
          'docm'                         => 'application/vnd.ms-word.document.macroEnabled.12',
          'dotx'                         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
          'dotm'                         => 'application/vnd.ms-word.template.macroEnabled.12',
          'xlsx'                         => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
          'xlsm'                         => 'application/vnd.ms-excel.sheet.macroEnabled.12',
          'xlsb'                         => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
          'xltx'                         => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
          'xltm'                         => 'application/vnd.ms-excel.template.macroEnabled.12',
          'xlam'                         => 'application/vnd.ms-excel.addin.macroEnabled.12',
          'pptx'                         => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
          'pptm'                         => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
          'ppsx'                         => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
          'ppsm'                         => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
          'potx'                         => 'application/vnd.openxmlformats-officedocument.presentationml.template',
          'potm'                         => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
          'ppam'                         => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
          'sldx'                         => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
          'sldm'                         => 'application/vnd.ms-powerpoint.slide.macroEnabled.12',
          'onetoc|onetoc2|onetmp|onepkg' => 'application/onenote',
          'oxps'                         => 'application/oxps',
          'xps'                          => 'application/vnd.ms-xpsdocument',
          // OpenOffice formats.
          'odt'                          => 'application/vnd.oasis.opendocument.text',
          'odp'                          => 'application/vnd.oasis.opendocument.presentation',
          'ods'                          => 'application/vnd.oasis.opendocument.spreadsheet',
          'odg'                          => 'application/vnd.oasis.opendocument.graphics',
          'odc'                          => 'application/vnd.oasis.opendocument.chart',
          'odb'                          => 'application/vnd.oasis.opendocument.database',
          'odf'                          => 'application/vnd.oasis.opendocument.formula',
          // WordPerfect formats.
          'wp|wpd'                       => 'application/wordperfect',
          // iWork formats.
          'key'                          => 'application/vnd.apple.keynote',
          'numbers'                      => 'application/vnd.apple.numbers',
          'pages'                        => 'application/vnd.apple.pages',
      )
    );
}

/**
 * Retrieves the list of common file extensions and their types.
 *
 * @since 4.6.0
 *
 * @return array[] Multi-dimensional array of file extensions types keyed by the type of file.
 */
function wp_get_ext_types() {

    /**
     * Filters file type based on the extension name.
     *
     * @since 2.5.0
     *
     * @see wp_ext2type()
     *
     * @param array[] $ext2type Multi-dimensional array of file extensions types keyed by the type of file.
     */
    return apply_filters(
      'ext2type',
      array(
        'image'       => array( 'jpg', 'jpeg', 'jpe', 'gif', 'png', 'bmp', 'tif', 'tiff', 'ico', 'heic', 'webp' ),
        'audio'       => array( 'aac', 'ac3', 'aif', 'aiff', 'flac', 'm3a', 'm4a', 'm4b', 'mka', 'mp1', 'mp2', 'mp3', 'ogg', 'oga', 'ram', 'wav', 'wma' ),
        'video'       => array( '3g2', '3gp', '3gpp', 'asf', 'avi', 'divx', 'dv', 'flv', 'm4v', 'mkv', 'mov', 'mp4', 'mpeg', 'mpg', 'mpv', 'ogm', 'ogv', 'qt', 'rm', 'vob', 'wmv' ),
        'document'    => array( 'doc', 'docx', 'docm', 'dotm', 'odt', 'pages', 'pdf', 'xps', 'oxps', 'rtf', 'wp', 'wpd', 'psd', 'xcf' ),
        'spreadsheet' => array( 'numbers', 'ods', 'xls', 'xlsx', 'xlsm', 'xlsb' ),
        'interactive' => array( 'swf', 'key', 'ppt', 'pptx', 'pptm', 'pps', 'ppsx', 'ppsm', 'sldx', 'sldm', 'odp' ),
        'text'        => array( 'asc', 'csv', 'tsv', 'txt' ),
        'archive'     => array( 'bz2', 'cab', 'dmg', 'gz', 'rar', 'sea', 'sit', 'sqx', 'tar', 'tgz', 'zip', '7z' ),
        'code'        => array( 'css', 'htm', 'html', 'php', 'js' ),
      )
    );
}

/**
 * Retrieve list of allowed mime types and file extensions.
 *
 * @since 2.8.6
 *
 * @param int|WP_User $user Optional. User to check. Defaults to current user.
 * @return string[] Array of mime types keyed by the file extension regex corresponding
 *                  to those types.
 */
function get_allowed_mime_types( $user = null ) {
    $t = wp_get_mime_types();

    unset( $t['swf'], $t['exe'] );
    if ( function_exists( 'current_user_can' ) ) {
        $unfiltered = $user ? user_can( $user, 'unfiltered_html' ) : current_user_can( 'unfiltered_html' );
    }

    if ( empty( $unfiltered ) ) {
        unset( $t['htm|html'], $t['js'] );
    }

    /**
     * Filters list of allowed mime types and file extensions.
     *
     * @since 2.0.0
     *
     * @param array            $t    Mime types keyed by the file extension regex corresponding to those types.
     * @param int|WP_User|null $user User ID, User object or null if not provided (indicates current user).
     */
    return apply_filters( 'upload_mimes', $t, $user );
}