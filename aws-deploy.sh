#!/bin/bash
version=v`date +%Y%m%d%H%M%S` &&

cd asciigram &&
zip -r ../$version.zip src vendor web \
    -x \*/.git\* \*/tests\* \*/Tests\* \
       \*/_samples\* \*/_docs\* \*/_compatibility_test\* &&
cd .. &&

s3cmd put $version.zip s3://elasticbeanstalk-us-east-1-837326383672 &&

elastic-beanstalk-create-application-version \
    -a asciigram \
    -d 'Version from Zip file' -l $version \
    -s elasticbeanstalk-us-east-1-837326383672/$version.zip &&

elastic-beanstalk-update-environment -e ascii-dev -l $version
