<?php

if( !function_exists('logger_main') )
{
    /**
     * @param int $log_level
     * @param int $mail_log_level
     * @param bool $cron
     */
    function logger_main(   $mailingList,
                            $log_level = null,
                            $mail_log_level = LOG_NOTICE,
                            $cron = false
        )
    {
        if(null == $log_level)
        {
            $log_level = get_default_log_level();
        }

        return function($msg, $level) use(  $mailingList,
                                            $log_level,
                                            $mail_log_level,
                                            $cron)
        {
            if( isCLI() )
            {
                if( $cron )
                {
                    if( $level <= LOG_ERR )
                    {
                        log_output( $msg, $level );
                    }
                    elseif( $level <= LOG_NOTICE )
                    {
                        log_mail( $mailingList, $msg, $level );
                    }
                }
                elseif( $level <= $log_level )
                {
                    log_output( $msg, $level );
                }
            }
            else
            {
                if( $level <= $mail_log_level )
                {
                    log_mail( $mailingList, $msg, $level );
                }
            }

            if( $level <= $log_level )
            {
                log_syslog( $msg, $level );
            }

        };
    }

    function log_mail( $mailingList, $msg, $level )
    {
        $host = gethostname();
        if( array_key_exists( 'HTTP_HOST', $_SERVER ) && $_SERVER['HTTP_HOST'] )
        {
            $host = $_SERVER[ 'HTTP_HOST' ];
        }

        mail( $mailingList, log_level_str($level) . ' on ' . $host, $msg );
    }

    /**
     * Logging to standard output
     *
     * @param string $msg
     * @param int $level PHP log levels
     */
    function log_output( $msg, $level )
    {
        writeln( $msg );
    }

    /**
     * Logging to syslog
     *
     * @param string $msg
     * @param int $level PHP log levels
     */
    function log_syslog( $msg, $level )
    {
        $identity = '';

        if( defined('HOST_GLOBAL') )
        {
            $identity .= HOST_GLOBAL;
        }
        else
        {
            $identity .= basename(ROOT_PATH);
        }

        openlog($identity, LOG_PID | LOG_ODELAY, LOG_USER);

        foreach( explode("\n", $msg) as $row )
        {
            syslog($level, str_replace("\t", '    ', $row));
        }
    }



}
