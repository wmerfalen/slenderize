# rdp-view-system

#### Description
A PHP package that attempts to mimic some of the unique qualities of slimrb. 

#### How it's done
We use a Recursive Descent Parser (hence the 'rdp' in this repo's title) to process the view's language grammar and then generate a PHP file from this intermediate syntax. Parsing only occurs once unless the file has been modified. 

#### Is it on packagist yet?
Unfortunately, no. The only way to use this code (at the moment) is to clone this repo. 
