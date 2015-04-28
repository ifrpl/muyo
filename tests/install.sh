#!/bin/bash

CURRENT_DIR=$(dirname "${BASH_SOURCE[0]}")

. $CURRENT_DIR/vars.sh

if [ -f $LIB_CONF_DIR/vars.sh ]; then
	. $LIB_CONF_DIR/vars.sh
fi

if [ -z $ROOT_DIR ]; then
	echo 'ROOT_DIR not defined'
	exit
fi

. $ROOT_DIR/bin/libs/bash/install.lib.sh

usage()
{
	cat << EOF
Usage:
	-h      Show this message
	-c      Composer

EOF

	exit
}

if [ 0 == $# ]; then
	usage
fi

c=0
o=0

while getopts "c:" OPTION
do
	case $OPTION in
		h)
            usage
			exit 1
			;;
		c)
			c=$OPTARG
			o=$OPTARG
			args=1
			;;
		?)
			usage
			exit
			;;
     esac
done

#

if [ 1 == $c ]; then

	section "PHP dependencies"

	if [ ! -f $COMPOSER_DIR/composer.phar ]; then
		scriptPath=/tmp/composer_setup.php
		assert "curl https://getcomposer.org/installer --output $scriptPath"
		assert "php $scriptPath --install-dir=$COMPOSER_DIR"
		rm $scriptPath
	fi

	assert "php $COMPOSER_DIR/composer.phar self-update"

	OPTION=install
	if [ -d vendor ]; then
		OPTION=update
	fi

	assert "php $COMPOSER_DIR/composer.phar $OPTION"

fi


if [ 1 == $o ]; then

	section "Composer autoload optimization"
	assert "php $COMPOSER_DIR/composer.phar dumpautoload --optimize"

fi




