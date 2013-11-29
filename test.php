<?php

include_once('./markdown.php');
$text = '
Paragraphs are separated by a blank line.

2nd paragraph. *Italic*, **bold**, `monospace`. Itemized lists
look like:

* this one
* that one
* the other one

Start the lines with blank space or a any of *, +, or - characters.

Here\'s a numbered list:

1. first thing
2. second thing
3. third thing


Nested lists look like this and can have mixed ordered/un-ordered parts.

1. First, get these ingredients:
  * carrots
  * celery
  * lentils
2. Boil some water.
3. Dump everything in the pot and follow this algorithm:  
1 find wooden spoon    
1 uncover pot  
1 stir  
1 cover pot  
1 balance wooden spoon precariously on pot handle  
1 wait 10 minutes  
1 goto first step (or shut off burner when done)  

Isn\'t it nice how text always lines up on 4-space indents?
Here\'s a link to [a website](http://foo.bar). Here\'s a link
to a [local doc](local-doc.html).

> This is the first level of quoting.
>
> > This is nested blockquote.
>
> Back to the first level.
';
echo Markdown($text);



