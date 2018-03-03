# rdp-view-system
[![Build Status](https://travis-ci.org/wmerfalen/rdp-view-system.svg?branch=master)](https://travis-ci.org/wmerfalen/rdp-view-system)

#### Description
A PHP package that attempts to mimic some of the unique qualities of slimrb. 

#### How it's done
We use a Recursive Descent Parser (hence the 'rdp' in this repo's title) to process the view's language grammar and then generate a PHP file from this intermediate syntax. Parsing only occurs once unless the file has been modified. 

# Syntax
#### Indentation
Indentation is required to open and close tags. 
```
html
	body
		div|This is a div
			p|this is a paragraph
```
The above code will generate the following html:
```
<html><body><div>This is a div<p>this is a paragraph</p></div></body></html>
```
#### Whitespace
The generated html is devoid of tab formatting to minimize memory footprint.
#### Literals
Literal strings start with the pipe character. 
```
html
	body
		div|This is a literal string
```
#### Variables
Embedding variables is very similar to most webdev frameworks
```
html
	body {{$my_attributes}}
		div|This is a div
```
This feature is available, but this project is still in dev. So while this syntax does work, there are still other things that need to be built for this to be useful. The Recursive Descent Parser, however, parses and generates the correct html.
# todo
* Setting variables to be sent to a view
* Variables that are objects and support for accessing properties 
* Supporting array subscripting of variables
* Embeddeding function calls into the view
* simple if/else/elseif statements
* looping constructs
