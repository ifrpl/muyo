#!/bin/bash

./vendor/bin/phpunit --log-junit junit-output.xml ./TestCase/Functions/*.php
./vendor/bin/phpunit --log-junit junit-output.xml ./TestCase/Model/*.php
