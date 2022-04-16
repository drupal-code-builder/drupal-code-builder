<?php

namespace DrupalCodeBuilder\PhpCsFixer;

class VariableStream {

    private $position;
    private $varname;

    public function stream_open( $path, $mode, $options, &$opened_path ) {
        $url            = parse_url( $path );
        $this->varname  = $url['host'];
        $this->position = 0;
        return true;
    }

    public function stream_read( $count ) {
        if ( ! isset( $GLOBALS[ $this->varname ] ) ) {
            return null;
        }
        $p   =& $this->position;
        $ret = substr( $GLOBALS[ $this->varname ], $p, $count );
        $p   += strlen( $ret );

        return $ret;
    }

    public function stream_write( $data ) {
        $v =& $GLOBALS[ $this->varname ];
        $l = strlen( $data );
        $p =& $this->position;
        $v = substr( $v, 0, $p ) . $data . substr( $v, $p += $l );

        return $l;
    }

    public function stream_tell() {
        return $this->position;
    }

    public function stream_eof() {
        if ( ! isset( $GLOBALS[ $this->varname ] ) ) {
            return false;
        }

        return $this->position >= strlen( $GLOBALS[ $this->varname ] );
    }

    public function stream_seek( $offset, $whence ) {
        $l = strlen( $GLOBALS[ $this->varname ] );
        $p =& $this->position;
        switch ( $whence ) {
            case SEEK_SET:
                $newPos = $offset;
                break;
            case SEEK_CUR:
                $newPos = $p + $offset;
                break;
            case SEEK_END:
                $newPos = $l + $offset;
                break;
            default:
                return false;
        }
        $ret = ( $newPos >= 0 && $newPos <= $l );
        if ( $ret ) {
            $p = $newPos;
        }

        return $ret;
    }

    public function mkdir() {
        return true;
    }

    /**
     * Called when the path is checked with file_exists(), is_writable() etc.
     * @return array|false
     */
    public function url_stat( string $path, int $flags ) {
        if ( ! $this->endsWith( $path, '.virtual.php' ) ) {
            return false;
        }

        return [
            'dev'     => 0,
            'ino'     => 0,
            'mode'    => 0100777,  // 0100000 + 0777 so that is_writable() yields true
            'nlink'   => 0,
            'uid'     => 0,
            'gid'     => 0,
            'rdev'    => 0,
            'size'    => isset( $GLOBALS[ $this->varname ] ) ? strlen( $GLOBALS[ $this->varname ] ) : 0,
            'atime'   => time(),
            'mtime'   => time(),
            'ctime'   => time(),
            'blksize' => 0,
            'blocks'  => 0,
        ];
    }

    public function stream_stat(): array {
        return array();
    }

    public function stream_set_option( int $option, int $arg1, ?int $arg2 = null ): bool {
        if ( ! isset( $GLOBALS[ $this->varname ] ) ) {
            return false;
        }
        if ( $option === STREAM_OPTION_BLOCKING ) {
            return stream_set_blocking( $GLOBALS[ $this->varname ], $arg1 );
        }
        if ( $option === STREAM_OPTION_READ_TIMEOUT ) {
            return stream_set_timeout( $GLOBALS[ $this->varname ], $arg1, $arg2 );
        }

        return stream_set_write_buffer( $GLOBALS[ $this->varname ], $arg2 ) === 0;
    }

    // Utilities
    function endsWith( $haystack, $needle ) {
        return substr_compare( $haystack, $needle, - strlen( $needle ) ) === 0;
    }

}
