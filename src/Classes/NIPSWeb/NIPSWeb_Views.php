<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace MyRadio\NIPSWeb;

/**
 * Description of NIPSWeb_Views
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 */
class NIPSWeb_Views
{
    public static function serveMP3($path)
    {
        //Set mp3 headers
        header('Content-Type: audio/mpeg');

        /**
         * Partial content support - this is required to set audio.currentTime
         * it will also help mitigate some issues with tracks pausing to buffer halfway through
         */
        if (!empty($_SERVER['HTTP_RANGE'])) {
            //Yeah, a byte range has been requested. We only serve part of the file at this time
            self::rangeDownload($path);
        } else {
            //This is a dumb read-whole-file request
            /**
             * @todo Investigate whether whole file requests are ever used if partial is available
             */
            //Get the size of the file
            header('Content-Length: ' . filesize($path));
            //Make sure it doesn't suddently not
            header('Connection: Keep-Alive');
            //Read the file
            readfile($path);
        }
    }

    public static function serveOGG($path)
    {
        //Set mp3 headers
        header('Content-Type: audio/ogg');

        /**
         * Partial content support - this is required to set audio.currentTime
         * it will also help mitigate some issues with tracks pausing to buffer halfway through
         */
        if (!empty($_SERVER['HTTP_RANGE'])) {
            //Yeah, a byte range has been requested. We only serve part of the file at this time
            self::rangeDownload($path);
        } else {
            //This is a dumb read-whole-file request
            /**
             * @todo Investigate whether whole file requests are ever used if partial is available
             */
            //Get the size of the file
            header('Content-Length: ' . filesize($path));
            //Make sure it doesn't suddently not
            header('Connection: Keep-Alive');
            //Read the file
            readfile($path);
        }
    }

    /**
     * Allows Partial Content Downloads - useful for audio and video streaming HTML5 stuff
     * From http://forums.phpfreaks.com/topic/106711-php-code-which-supports-byte-range-downloads-for-iphone/
     * @param String $file path to the file
     */
    public static function rangeDownload($file)
    {
        $fp = @fopen($file, 'rb');

        $size = filesize($file); // File size
        $length = $size;           // Content length
        $start = 0;               // Start byte
        $end = $size - 1;       // End byte
        // Now that we've gotten so far without errors we send the accept range header
        /* At the moment we only support single ranges.
         * Multiple ranges requires some more work to ensure it works correctly
         * and comply with the spesifications: http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
         *
         * Multirange support annouces itself with:
         * header('Accept-Ranges: bytes');
         *
         * Multirange content must be sent with multipart/byteranges mediatype,
         * (mediatype = mimetype)
         * as well as a boundry header to indicate the various chunks of data.
         */
        header("Accept-Ranges: 0-$length");
        // header('Accept-Ranges: bytes');
        // multipart/byteranges
        // http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
        if (isset($_SERVER['HTTP_RANGE'])) {
            $c_start = $start;
            $c_end = $end;
            // Extract the range string
            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
            // Make sure the client hasn't sent us a multibyte range
            if (strpos($range, ',') !== false) {
                // (?) Shoud this be issued here, or should the first
                // range be used? Or should the header be ignored and
                // we output the whole content?
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                // (?) Echo some info to the client?
                exit;
            }
            // If the range starts with an '-' we start from the beginning
            // If not, we forward the file pointer
            // And make sure to get the end byte if spesified
            if ($range{0} == '-') {
                // The n-number of the last bytes is requested
                $c_start = $size - substr($range, 1);
            } else {
                $range = explode('-', $range);
                $c_start = $range[0];
                $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
            }
            /* Check the range and make sure it's treated according to the specs.
             * http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
             */
            // End bytes can not be larger than $end.
            $c_end = ($c_end > $end) ? $end : $c_end;
            // Validate the requested range and return an error if it's not correct.
            if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                // (?) Echo some info to the client?
                exit;
            }
            $start = $c_start;
            $end = $c_end;
            $length = $end - $start + 1; // Calculate new content length
            fseek($fp, $start);
            header('HTTP/1.1 206 Partial Content');
        }
        // Notify the client the byte range we'll be outputting
        header("Content-Range: bytes $start-$end/$size");
        header("Content-Length: $length");

        // Start buffered download
        $buffer = 1024 * 8;
        while (!feof($fp) && ($p = ftell($fp)) <= $end) {
            if ($p + $buffer > $end) {
                // In case we're only outputtin a chunk, make sure we don't
                // read past the length
                $buffer = $end - $p + 1;
            }
            set_time_limit(0); // Reset time limit for big files
            echo fread($fp, $buffer);
            flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
        }

        fclose($fp);
    }
}
