Asciigram
=========

This is a dirty, little Silex application to demonstrate integration with the AWS PHP SDK.

There is currently no deployed version while I update things for Sunshine PHP.

The accompanying presentation that went with this code, followed these commands:

```bash
# Into home directory and talk directory
cd
cd ground-up
rm awesome-presentation.pdf

# Who am I?
finger rowan

# Create a basic app to test
mkdir asciigram
cd asciigram

# Drop in the standard Composer file for Silex
vim composer.json
curl -s http://getcomposer.org/installer | php
./composer.phar install

# Make the root
mkdir web
vim web/humans.txt
vim web/info.php
vim web/.htaccess
vim web/index.php

tree -L 2
# Test the application
php -S localhost:8080 -t web

# Split the terminal and test
curl -v http://localhost:8080/hello && echo

# Commit the code so far
vim .gitignore
git init
git add .
git commit -m "Initial commit with base Silex application"

cd ..

# Elastic Beanstalk
# Get the AWS Elastic Beanstalk command line tools
# http://aws.amazon.com/code/6752709412171743
# wget https://s3.amazonaws.com/elasticbeanstalk/cli/elasticbeanstalk-cli.zip
wget \
    https://s3.amazonaws.com/elasticbeanstalk/cli/AWS-ElasticBeanstalk-CLI-2.1.zip
unzip elasticbeanstalk-cli.zip

# Add the tools to the path
export PATH=$PATH:`pwd`/AWS-ElasticBeanstalk-CLI-2.1/eb/linux/python2.7

# test path
eb --help

# Set up access for our developer
column -s, -t iam-login.credentials.csv
column -s, -t iam-access.credentials.csv

cat aws.credentials
chmod 0600 aws.credentials
export AWS_CREDENTIAL_FILE=`pwd`/aws.credentials

cd asciigram

eb init

ls .elasticbeanstalk
cat .elasticbeanstalk/config

eb start

curl -s http://asciigram-dev-32mhrjbris.elasticbeanstalk.com | head -n 30

ls .elasticbeanstalk/
vim .elasticbeanstalk/optionsettings
git status
git diff
git add .gitignore
git commit -m "Initial EB integration"

eb updates

git aws.push

eb status

curl -v http://asciigram-dev-32mhrjbris.elasticbeanstalk.com
curl -v http://asciigram-dev-32mhrjbris.elasticbeanstalk.com/humans.txt

eb delete

cd ..

export PATH=$PATH:`pwd`/AWS-ElasticBeanstalk-CLI-2.1/api/bin

elastic-beanstalk-list-available-solution-stacks

# Create an application
elastic-beanstalk-create-application --help
elastic-beanstalk-create-application -a asciigram \
    -d "Example application for Elastic Beanstalk testing"

# Create the application version
# Can also auto-create the app but thought we'd be specific
elastic-beanstalk-create-application-version --help
elastic-beanstalk-create-application-version \
    -a asciigram \
    -d "Initial development version" \
    -l v-dev-001

# Create an environment and launch it
elastic-beanstalk-create-environment --help
elastic-beanstalk-create-environment \
    -e ascii-dev -a asciigram  -l v-dev-001 \
    -d "Development environment" \
    -s "64bit Amazon Linux running PHP 5.3"

# Sit back and wait for it to launch
elastic-beanstalk-describe-environments -e ascii-dev
elastic-beanstalk-describe-environments -j | python -mjson.tool
elastic-beanstalk-describe-environments -j \
    | python -mjson.tool | grep Status

elastic-beanstalk-describe-environments -j \
    | python -mjson.tool | grep Health


# Set the document root
# First grab the options and search for document_root
elastic-beanstalk-describe-configuration-settings \
    -a asciigram -e ascii-dev -j | python -mjson.tool | less
# Copy into a file and edit
vim config.document_root.json

# Send the configuration back to the environment
elastic-beanstalk-update-environment --help
elastic-beanstalk-update-environment \
    -e ascii-dev -l v-dev-001 -f config.document_root.json
elastic-beanstalk-describe-configuration-settings \
    -a asciigram -e ascii-dev -j \
    | python -mjson.tool | grep -C2 document_root

# Time for some more AWS tools
sudo apt-get install s3cmd
s3cmd --configure

# List versions
s3cmd -ls

zip -r ../v`date +%Y%m%d%H%M%S`.zip vendor web \
    -x \*/.git\* \*/Tests\*


s3cmd ls s3://elasticbeanstalk-us-east-1-837326383672

zip -r ../v`date +%Y%m%d%H%M%S`.zip vendor web

cd ..

s3cmd put v*.zip s3://elasticbeanstalk-us-east-1-837326383672

# Upload the new manual version
elastic-beanstalk-create-application-version \
    -a asciigram -d 'Version from Zip file' \
    -l v20120912113006 \
    -s elasticbeanstalk-us-east-1-837326383672/v20120912113006.zip


elastic-beanstalk-update-environment \
    -e ascii-dev -l v20120912113006

curl -v http://ascii-dev-vfnuwuvfjh.elasticbeanstalk.com

# Quickly wrap that up with a script
vim aws-deploy.sh

# Let's quickly drop in some stuff from https://github.com/qpleple/silex-bootstrap and https://github.com/lyrixx/Silex-Kitchen-Edition
cd asciigram
# Time to integrate the AWS SDK
# Using a fork from https://github.com/rcambien/aws-sdk-for-php for Composer support
# https://github.com/amazonwebservices/aws-sdk-for-php/pull/29
# The SDK also has weird ideas about where it stores the config, so Composer lets us avoid that
vim composer.json
./composer.phar update

# Take a look at what's in there
ls vendor/amazonwebservices/aws-sdk-for-php/services/

# However, we still need to pass that config to the classes, so we'll store it on the app
vim src/config.php

tree -L 2 src

# Bootstrap our own namespace
vim src/bootstrap.php

vim src/app.php

google-chrome --incognito --app \
    http://ascii-dev-vfnuwuvfjh.elasticbeanstalk.com/upload \
    > /dev/null 2>&1

vim src/Asciigram/ImageUploader.php
vim src/Asciigram/S3Service.php
vim src/Asciigram/ImageUploader.php
vim src/Asciigram/SNSService.php
vim src/app.php
vim src/Asciigram/ImageTransformer.php
vim src/Asciigram/SNSService.php
vim src/Asciigram/ImageTransformer.php
vim src/Asciigram/S3Service.php

google-chrome --incognito --app \
    http://ascii-dev-vfnuwuvfjh.elasticbeanstalk.com/ \
    > /dev/null 2>&1

```
