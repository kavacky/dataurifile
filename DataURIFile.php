<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * A simple class for Data URI handling.
 *
 * Initially made for Kohana, but requires only exception rewriting to fit
 * anything else.
 *
 * @author   Viesturs Kavacs <kavackys@gmail.com>
 * @link     https://github.com/kavacky/dataurifile
 */
class DataURIFile {
    protected $data_uri = '';
    protected $mime_type = null;
    protected $data = null;
    protected $size = null;
    protected $filename = null;

    protected $allowed = array(
        'size' => 0, // Bytes
        'mime_types' => array(),
    );

    /**
     *
     */
    public function __construct($data_uri = '') {
        $this->data_uri = $data_uri;
    }

    /**
     * @return DataURLFile
     * @throws Kohana_Exception
     */
    public function decode() {
        $parts = array();
        if (!preg_match('/data:([^;]*);base64,(.*)/', $this->data_uri, $parts)) {
            throw new Kohana_Exception('Invalid Data URI');
        }

        $this->mime_type = $parts[1];
        $this->data = base64_decode($parts[2]);
        $this->size = strlen($this->data);

        return $this;
    }

    /**
     * @return DataURLFile
     * @throws Kohana_Exception
     */
    public function check($override = array()) {
        $allowed = array_merge($this->allowed, $override);

        if ($this->size > $allowed['size']) {
            throw new Kohana_Exception(
                'Allowed file size exceeded: :has bytes instead of maximum of :allowed',
                array(
                    ':has' => $this->size,
                    ':allowed' => $allowed['size'],
                )
            );
        }

        if (!in_array($this->mime_type, $allowed['mime_types'])) {
            throw new Kohana_Exception(
                'Mime type not allowed: :has (Allowed: :allowed)',
                array(
                    ':has' => $this->mime_type,
                    ':allowed' => print_r($allowed['mime_types'], true),
                )
            );
        }

        return $this;
    }

    /**
     * @throws Kohana_Exception
     */
    public function save($filename, $mode = 0644, $dirmode = 0755) {
        $path = dirname($filename);
        if (!is_dir($path)) {
            if (mkdir($path, $dirmode, true)) {
                throw new Kohana_Exception(
                    'Cannot create directory :dir',
                    array(':dir' => Debug::path($path))
                );
            }
        }

        if (file_put_contents($filename, $this->data) == false) {
            throw new Kohana_Exception(
                'Cannot write file :f',
                array(':f' => Debug::path($filename))
            );
        }

        if (!chmod($filename, $mode)) {
            throw new Kohana_Exception(
                'Cannot chmod file :f',
                array(':f' => Debug::path($filename))
            );
        }

        $this->filename = $filename;
        return $this->filename;
    }
}