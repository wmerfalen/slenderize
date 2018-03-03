# rdp-view-system

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
# todo
* Embedding variables into the view 
* Embeddeding function calls into the view
* simple if/else/elseif statements
* looping constructs
