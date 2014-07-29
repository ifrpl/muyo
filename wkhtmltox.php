<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)


if( !defined('WK_EXECUTABLE_PREFIX') )
{
	define('WK_EXECUTABLE_PREFIX','xvfb-run --auto-servernum /usr/local/bin/');
}

if( !defined('WK_EXECUTABLE_IMG') )
{
	define('WK_EXECUTABLE_IMG','wkhtmltoimage');
}
if( !defined('WK_EXECUTABLE_PDF') )
{
	define('WK_EXECUTABLE_PDF','wkhtmltopdf.0.11.0_rc1');
}


$wk_default_opt_pdf = array();
function wk_pdf_cli_build($input,$output,$options)
{
	global $wk_default_opt_pdf;

	$o = $wk_default_opt_pdf;
	foreach($options as $k => $v)
	{
		$o[$k] = $v;
	}

	return WK_EXECUTABLE_PREFIX . WK_EXECUTABLE_PDF . wk_opts_build($input,$output,$options);
}

$wk_default_opt_img = array();
function wk_img_cli_build($input,$output,$options)
{
	global $wk_default_opt_img;

	$o = $wk_default_opt_img;
	foreach($options as $k => $v)
	{
		$o[$k] = $v;
	}

	return WK_EXECUTABLE_PREFIX . WK_EXECUTABLE_IMG . wk_opts_build($input,$output,$options);
}

function wk_opts_build($input, $output, $options)
{
	$command = "";

	foreach($options as $key => $option)
	{
		if( null !== $option && false !== $option )
		{
			if( true === $option )
			{
				$command .= ' --'.$key;
			}
			elseif( is_array($option) )
			{
				if( is_array_assoc($option) )
				{
					foreach($option as $k => $v)
					{
						$command .= ' --'.$key.' '.escapeshellarg($k).' '.escapeshellarg($v);
					}
				}
				else
				{
					foreach($option as $v)
					{
						$command .= " --".$key." ".escapeshellarg($v);
					}
				}

			}
			else
			{
				$command .= ' --'.$key." ".escapeshellarg($option);
			}
		}
	}

	$command .= ' '.escapeshellarg($input).' '.escapeshellarg($output);
	return $command;
}

/**
 * Convert HTML file to pdf and save it to $target
 * @param string $source path
 * @param string $target path
 * @param array $options
 */
function wk_conv_file_to_pdf_file($source,$target,$options)
{
	debug_enforce( file_exists($source) && is_file($source), "File '{$source}' doesn't exists." );
	proc_exec(wk_pdf_cli_build($source,$target,$options));
	debug_enforce( file_exists($target) && is_file($target) && is_readable($target), "Could not generate '{$source}'." );
}

/**
 * Convert HTML file to image and save it to $target
 * @param string $source path
 * @param string $target path
 * @param array $options
 */
function wk_conv_file_to_img_file($source,$target,$options)
{
	debug_enforce( file_exists($source) && is_file($source), "File '{$source}' doesn't exists." );
	proc_exec(wk_pdf_cli_build($source,$target,$options));
	debug_enforce( file_exists($target) && is_file($target) && is_readable($target), "Could not generate '{$source}'." );
}

/**
 * Convert HTML file to pdf and return it as string
 * @param string $source path
 * @param array $options
 * @return string
 */
function wk_conv_file_to_pdf_str($source,$options)
{
	$target = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('ifr_wkhtmltox') . '.pdf';
	wk_conv_file_to_pdf_file($source,$target,$options);
	$ret = file_get_contents($target);

	if(!App_Application::isDevEnv())
	{
		debug_enforce(unlink($target),"Cannot delete temporary file '{$target}'");
	}

	return $ret;
}

/**
 * Convert HTML file to image and return it as string
 * @param string $source path
 * @param array $options
 * @return string
 */
function wk_conv_file_to_img_str($source,$options)
{
	$target = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('ifr_wkhtmltox') . '.jpeg';
	wk_conv_file_to_img_file($source,$target,$options);
	$ret = file_get_contents($target);
	debug_enforce(unlink($target),"Cannot delete temporary file '{$target}'");
	return $ret;
}

/**
 * Convert HTML string to pdf and return it as string
 * @param string $html
 * @param array $options
 * @return string
 */
function wk_conv_str_to_pdf_str($html,$options)
{
	$source = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('ifr_wkhtmltox') . '.html';
	file_put_contents($source,$html);
	$ret = wk_conv_file_to_pdf_str($source,$options);

	if(!App_Application::isDevEnv())
	{
		debug_enforce(unlink($source),"Cannot delete temporary file '{$source}'");
	}

	return $ret;
}

/**
 * Convert HTML string to image and return it as string
 * @param string $html
 * @param array $options
 * @return string
 */
function wk_conv_str_to_img_str($html,$options)
{
	$source = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('ifr_wkhtmltox') . '.html';
	debug_enforce( false !== file_put_contents($source,$html), "Cannot create temporary file {$source}" );
	$ret = wk_conv_file_to_img_str($source,$options);
	debug_enforce(unlink($source),"Cannot delete temporary file '{$source}'");
	return $ret;
}

/**
 * Convert HTML string to pdf file
 * @param string $html
 * @param string $target path
 * @param array $options
 */
function wk_conv_str_to_pdf_file($html,$target,$options)
{
	$source = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('ifr_wkhtmltox') . '.html';
	debug_enforce( false !== file_put_contents($source,$html), "Cannot create temporary file {$source}" );
	wk_conv_file_to_pdf_file($source,$target,$options);
	debug_enforce(unlink($source),"Cannot delete temporary file '{$source}'");
}

/**
 * Convert HTML string to image file
 * @param string $html
 * @param string $target path
 * @param array $options
 */
function wk_conv_str_to_img_file($html,$target,$options)
{
	$source = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('ifr_wkhtmltox') . '.html';
	debug_enforce( false !== file_put_contents($source,$html), "Cannot create temporary file {$source}" );
	wk_conv_file_to_img_file($source,$target,$options);
	debug_enforce(unlink($source),"Cannot delete temporary file '{$source}'");
}