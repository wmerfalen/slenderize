# slenderize

### Slenderize is a minimalistic view system for PHP.

[![Build Status](https://travis-ci.org/wmerfalen/slenderize.svg?branch=master)](https://travis-ci.org/wmerfalen/slenderize)

#### Current Version
1.3

#### Description
A PHP package that attempts to mimic some of the unique qualities of slimrb. 

#### Supported Platforms
Right now, I only have access to a linux box. This should have no problem working on MacOS or any BSD variants. Windows support is unknown as I haven't tested on that platform. 

#### How it's done
We use a Recursive Descent Parser to process the view's language grammar and then generate a PHP file from this intermediate syntax. Parsing only occurs once unless the file has been modified. 

# Usage
### Configuration
Code that utilizes this library must set config values using the Config class
```
<?php
	require 'vendor/autoload.php';

	/** These two lines are required */
	\slenderize\Config::set('view_dir','/path/to/view_dir/');
	\slenderize\Config::set('cache_dir','/path/to/cache_dir/');
```

### Serving a view
Use the \slenderize\Page object to serve a page to the user
```
	$page = new \slenderize\Page('my_view');	/* Open and parse /path/to/view_dir/my_view */
	$page->view();	/* Will echo out the html generated */

	/* Or you if you like code golf: */
	new \slenderize\Page('my_view')->view();
```

### Exceptions
All exceptions that are thrown by the \slenderize namespace inherit from \Exception, so you can simply catch \Exception
```
	try {
		$page = new \slenderize\Page('my_view');
	}catch(\Exception $e){
		/** handle exception here */
	}
```

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
	body
		{{$my_attributes}}
		div|This is a div
```
The HTML that is generated by the above code snippet is dependant largely on what is in the variable $my_attributes. 
#### Static variables
Embedding static variables is just like in PHP
```
html
	body
		{{\My\Class::$my_static_var}}
		div|This is a div
```
#### Calling functions on objects
Objects can be passed to the Page and, while inside the view, an instance method can be called on that object.
```
html
	body
		{{$my_object->my_function()}}
		div|This is a div
```
NOTE: Currently, there is no way to pass anything to functions.
#### Calling static methods
Static calls are just like in PHP
```
html
	body
		{{\Foo\Bar\MyNamespace::my_function()}}
		div|This is a div
```
NOTE: Currently, there is no way to pass anything to functions.

# Recently added
* Calling static methods
* Embedding static variables
* Embedding function calls into the view

# todo
* Variables that are objects and support for accessing properties 
* Supporting array subscripting of variables
* simple if/else/elseif statements
* looping constructs
