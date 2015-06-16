#!/bin/bash

./vendor/bin/phpunit --log-junit junit-output-functions.xml ./TestCase/Functions/*.php
./vendor/bin/phpunit --log-junit junit-output-model.xml ./TestCase/Model/*.php
