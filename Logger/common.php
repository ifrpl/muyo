<?php

if( !function_exists('logger_common') )
{
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

    function get_default_log_level()
    {
        $env = getCurrentEnv();
        switch ($env)
        {
            case ENV_DEVELOPMENT:
                $log_level = LOG_DEBUG;
                break;
            case ENV_PRODUCTION:
                $log_level = LOG_NOTICE;
                break;
            case 'testing':
            default:
                $log_level = LOG_INFO;
                break;
        }

        return $log_level;
    }

    /**
     * @param int $level
     * @return string
     */
    function log_level_str($level)
    {
        $map = array(
            LOG_EMERG => 'EMERG',
            LOG_ALERT => 'ALERT',
            LOG_CRIT => 'CRITICAL',
            LOG_ERR => 'ERROR',
            LOG_WARNING => 'WARNING',
            LOG_NOTICE => 'NOTICE',
            LOG_INFO => 'INFO',
            LOG_DEBUG => 'DEBUG',
        );
        if( debug_assert( array_key_exists( $level, $map ), 'Unknown log level' ) )
        {
            $level = $map[ $level ];
        }
        return $level;
    }
}
