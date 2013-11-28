<?php
//
// Markdown Marked down - A text-to-HTML conversion tool for web writers with fewer markdown text and a bit updated
//
// PHP Markdown Marked down
// Copyright (c) 2004-2012 Michel Fortin
// <http://michelf.com/projects/php-markdown/>
//
// PHP Markdown
// Copyright (c) 2004-2012 Michel Fortin
// <http://michelf.com/projects/php-markdown/>
//
// Original Markdown
// Copyright (c) 2004-2006 John Gruber
// <http://daringfireball.net/projects/markdown/>
//

define ('MARKDOWN_VERSION', "1.0.2"); // Sun 8 Jan 2012

function Markdown($text)
{
    //
    // Initialize the parser and return the result of its transform method.
    //
    // Setup static parser variable.
    static $parser;
    if (!isset ($parser)) {
        $parser = new Markdown ();
    }

    // Transform text using parser.
    return $parser->transform($text);
}

//
// Markdown Parser Class
//
class Markdown
{
    // Internal hashes used during transformation.
    private $html_hashes = array();

    function Markdown()
    {
        //
        // Constructor function. Initialize appropriate member variables.
        //
        $this->prepareItalicsAndBold();
        // Sort document, block, and span gamut in ascendent priority order.
        asort($this->block_gamut);
        asort($this->span_gamut);
    }

    function setup()
    {
        // Clear global hashes.
        $this->html_hashes = array();
    }

    function teardown()
    {
        //
        // Called after the transformation process to clear any variable
        // which may be taking up memory unnecessarly.
        //
        $this->html_hashes = array();
    }

    function transform($text)
    {
        //
        // Main function. Performs some preprocessing on the input text
        // and pass it through the document gamut.
        //
        $this->setup();

        // Remove UTF-8 BOM and marker character in input, if present.
        $text = preg_replace('{^\xEF\xBB\xBF|\x1A}', '', $text);

        // Standardize line endings:
        // DOS to Unix and Mac to Unix
        $text = preg_replace('{\r\n?}', "\n", $text);

        // Make sure $text ends with a couple of newlines:
        $text .= "\n\n";

        // Convert all tabs to spaces.
        $text = $this->detab($text);

        // Turn block-level HTML blocks into hash entries
        $text = $this->hashHTMLBlocks($text);

        // Strip any lines consisting only of spaces and tabs.
        // This makes subsequent regexen easier to write, because we can
        // match consecutive blank lines with /\n+/ instead of something
        // contorted like /[ ]*\n+/ .
        $text = preg_replace('/^[ ]+$/m', '', $text);

        // Run document gamut methods.
        $text = $this->runBasicBlockGamut($text);

        $this->teardown();

        return $text . "\n";
    }

    function hashHTMLBlocks($text)
    {
        // Hashify HTML blocks:
        // We only want to do this for block-level HTML tags, such as headers,
        // lists, and tables. That's because we still want to wrap <p>s around
        // "paragraphs" that are wrapped in non-block-level tags, such as
        // anchors,
        // phrase emphasis, and spans. The list of tags we're looking for is
        // hard-coded:
        //
        // * List "a" is made of tags which can be both inline or block-level.
        // These will be treated block-level when the start tag is alone on
        // its line, otherwise they're not matched here and will be taken as
        // inline later.
        // * List "b" is made of tags which are always block-level;
        //
        $block_tags_a_re = 'ins|del';
        $block_tags_b_re = 'p|div|h[1-6]|blockquote|pre|table|dl|ol|ul|address|script|noscript|form|fieldset|iframe|math';

        // Regular expression for the content of a block tag.
        $nested_tags_level = 4;
        $attr = '
				(?>				# optional tag attributes
				\s			# starts with whitespace
				(?>
				[^>"/]+		# text outside quotes
			  |
				/+(?!>)		# slash not followed by ">"
			  |
				"[^"]*"		# text inside double quotes (tolerate ">")
			  |
				\'[^\']*\'	# text inside single quotes (tolerate ">")
			  )*
			)?
			';
        $content = str_repeat(
                '
                               (?>
                                 [^<]+				# content without tag
                                       |
                                       <\2			# nested opening tag
                                   ' . $attr . '	# attributes
					(?>
					  />
					|
					  >',
                $nested_tags_level
            ) . // end of opening tag
            '.*?' . // last level nested tag content
            str_repeat(
                '
                                   </\2\s*>	# closing nested tag
                                   )
                                   |
                                   <(?!/\2\s*>	# other tags with a different name
                                 )
                               )*',
                $nested_tags_level
            );
        $content2 = str_replace('\2', '\3', $content);

        // First, look for nested blocks, e.g.:
        // <div>
        // <div>
        // tags for inner block must be indented.
        // </div>
        // </div>
        //
        // The outermost tags must start at the left margin for this to match,
        // and
        // the inner nested divs must be indented.
        // We need to do this before the next, more liberal match, because the
        // next
        // match will start at the first `<div>` and stop at the first `</div>`.
        $text = preg_replace_callback(
            '{(?>
                                   (?>
                                   (?<=\n\n)		# Starting after a blank line
                           |				# or
                           \A\n?			# the beginning of the doc
                       )
                       (						# save in $1

                         # Match from `\n<tag>` to `</tag>\n`, handling nested tags
                         # in between.

                                   [ ]{0,1}
                                   <(' . $block_tags_b_re . ')# start tag = $2
									' . $attr . '>			# attributes followed by > and \n
						' . $content . '		# content, support nesting
							</\2>				# the matching end tag
						[ ]*				# trailing spaces/tabs
						(?=\n+|\Z)	# followed by a newline or end of document
	
			| # Special version for tags of group a.
	
						[ ]{0,1}
						<(' . $block_tags_a_re . ')# start tag = $3
							' . $attr . '>[ ]*\n	# attributes followed by >
							' . $content2 . '		# content, support nesting
							</\3>				# the matching end tag
						[ ]*				# trailing spaces/tabs
						(?=\n+|\Z)	# followed by a newline or end of document
			
			| # Special case just for <hr />. It was easier to make a special
			  # case than to make the other regex more complicated.
		
						[ ]{0,1}
						<(hr)				# start tag = $2
							' . $attr . '			# attributes
						/?>					# the matching end tag
						[ ]*
						(?=\n{2,}|\Z)		# followed by a blank line or end of document
		
			| # Special case for standalone HTML comments:
		
					[ ]{0,1}
					(?s:
						<!-- .*? -->
		)
		[ ]*
					(?=\n{2,}|\Z)		# followed by a blank line or end of document
		
			| # PHP and ASP-style processor instructions (<? and <%)
		
					[ ]{0,1}
					(?s:
						<([?%])			# $2
				.*?
				\2>
				)
					[ ]*
					(?=\n{2,}|\Z)		# followed by a blank line or end of document
			
			)
			)}Sxmi',
            function ($matches) {
                $text = $matches [1];
                $key = $this->hashBlock($text);
                return "\n\n$key\n\n";
            },
            $text
        );

        return $text;
    }

    function hashPart($text, $boundary = 'X')
    {
        //
        // Called whenever a tag must be hashed when a function insert an atomic
        // element in the text stream. Passing $text to through this function
        // gives
        // a unique text-token which will be reverted back when calling unhash.
        //
        // The $boundary argument specify what character should be used to
        // surround
        // the token. By convension, "B" is used for block elements that needs
        // not
        // to be wrapped into paragraph tags at the end, ":" is used for
        // elements
        // that are word separators and "X" is used in the general case.
        //
        // Swap back any tag hash found in $text so we do not have to `unhash`
        // multiple times at the end.
        $text = $this->unhash($text);

        // Then hash the block.
        static $i = 0;
        $key = $boundary . "\x1A" . ++$i . $boundary;
        $this->html_hashes [$key] = $text;
        return $key; // String that will replace the tag.
    }

    function hashBlock($text)
    {
        //
        // Shortcut function for hashPart with block-level boundaries.
        //
        return $this->hashPart($text, 'B');
    }

    var $block_gamut = array(
        //
        // These are all the transformations that form block-level
        // tags like paragraphs, headers, and list items.
        //
        "doHorizontalRules" => 20,
        "doLists" => 40,
        "doBlockQuotes" => 60
    );

    function runBlockGamut($text)
    {
        //
        // Run block gamut tranformations.
        //
        // We need to escape raw HTML in Markdown source before doing anything
        // else. This need to be done for each block, and not only at the
        // begining in the Markdown function since hashed blocks can be part of
        // list items and could have been indented. Indented blocks would have
        // been seen as a code block in a previous pass of hashHTMLBlocks.
        $text = $this->hashHTMLBlocks($text);

        return $this->runBasicBlockGamut($text);
    }

    function runBasicBlockGamut($text)
    {
        //
        // Run block gamut tranformations, without hashing HTML blocks. This is
        // useful when HTML blocks are known to be already hashed, like in the
        // first
        // whole-document pass.
        //
        foreach ($this->block_gamut as $method => $priority) {
            $text = $this->$method ($text);
        }

        // Finally form paragraph and restore hashed blocks.
        $text = $this->formParagraphs($text);

        return $text;
    }

    function doHorizontalRules($text)
    {
        // Do Horizontal Rules:
        return preg_replace(
            '{
                               ^[ ]{0,3}	# Leading space
                               ([-*_])		# $1: First marker
                           (?>			# Repeated marker group
                               [ ]{0,2}	# Zero, one, or two spaces.
                               \1			# Marker character
                           ){2,}		# Group repeated at least twice
                           [ ]*		# Tailing spaces
                           $			# End of line.
                       }mx',
            "\n" . $this->hashBlock("<hr>") . "\n",
            $text
        );
    }

    var $span_gamut = array(
        //
        // These are all the transformations that occur *within* block-level
        // tags like paragraphs, headers, and list items.
        //
        // Process character escapes, code spans, and inline HTML
        // in one shot.
        "parseSpan" => -30,
        // Make links out of things like `<http://example.com/>`
        // Must come after doAnchors, because you can use < and >
        // delimiters in inline links like [this](<url>).
        "encodeAmpsAndAngles" => 40,
        "doItalicsAndBold" => 50,
        "doHardBreaks" => 60
    );

    function runSpanGamut($text)
    {
        //
        // Run span gamut tranformations.
        //
        foreach ($this->span_gamut as $method => $priority) {
            $text = $this->$method ($text);
        }

        return $text;
    }

    function doHardBreaks($text)
    {
        // Do hard breaks:
        return preg_replace_callback(
            '/ {2,}\n/',
            function ($matches) {
                return $this->hashPart("<br>\n");
            },
            $text
        );
    }

    function doLists($text)
    {
        // Re-usable patterns to match list item bullets and number markers:
        $marker_ul_re = '[*+-]';
        $marker_ol_re = '\d+[\.]';
        $marker_any_re = "(?:$marker_ul_re|$marker_ol_re)";

        $markers_relist = array(
            $marker_ul_re => $marker_ol_re,
            $marker_ol_re => $marker_ul_re
        );

        foreach ($markers_relist as $marker_re => $other_marker_re) {
            // Re-usable pattern to match any entirel ul or ol list:
            $whole_list_re = '
								(								# $1 = whole list
				  (								# $2
					([ ]{0,1})	# $3 = number of spaces
						(' . $marker_re . ')			# $4 = first list item marker
					[ ]+
				  )
				  (?s:.+?)
				  (								# $5
					  \z
					|
					  \n{2,}
					  (?=\S)
					  (?!						# Negative lookahead for another list item marker
						[ ]*
						' . $marker_re . '[ ]+
					  )
								|
						  (?=						# Lookahead for another kind of list
					    \n
						\3						# Must have the same indentation
						' . $other_marker_re . '[ ]+
										)
		)
			)
			'; // mx

            // We use a different prefix before nested lists than top-level
            // lists.
            // See extended comment in _ProcessListItems().

            if ($this->list_level) {
                $text = preg_replace_callback(
                    '{
                                       ^
                                       ' . $whole_list_re . '
				}mx',
                    array(
                        &$this,
                        '_doLists_callback'
                    ),
                    $text
                );
            } else {
                $text = preg_replace_callback(
                    '{
                                       (?:(?<=\n)\n|\A\n?) # Must eat the newline
                                           ' . $whole_list_re . '
					}mx',
                    array(
                        &$this,
                        '_doLists_callback'
                    ),
                    $text
                );
            }
        }

        return $text;
    }

    function _doLists_callback($matches)
    {
        // Re-usable patterns to match list item bullets and number markers:
        $marker_ul_re = '[*+-]';
        $marker_ol_re = '\d+[\.]';
        $marker_any_re = "(?:$marker_ul_re|$marker_ol_re)";

        $list = $matches [1];
        $list_type = preg_match("/$marker_ul_re/", $matches [4]) ? "ul" : "ol";

        $marker_any_re = ($list_type == "ul" ? $marker_ul_re : $marker_ol_re);

        $list .= "\n";
        $result = $this->processListItems($list, $marker_any_re);

        $result = $this->hashBlock("<$list_type>\n" . $result . "</$list_type>");
        return "\n" . $result . "\n\n";
    }

    var $list_level = 0;

    function processListItems($list_str, $marker_any_re)
    {
        //
        // Process the contents of a single ordered or unordered list, splitting
        // it
        // into individual list items.
        //
        // The $this->list_level global keeps track of when we're inside a list.
        // Each time we enter a list, we increment it; when we leave a list,
        // we decrement. If it's zero, we're not in a list anymore.
        //
        // We do this because when we're not inside a list, we want to treat
        // something like this:
        //
        // I recommend upgrading to version
        // 8. Oops, now this line is treated
        // as a sub-list.
        //
        // As a single paragraph, despite the fact that the second line starts
        // with a digit-period-space sequence.
        //
        // Whereas when we're inside a list (or sub-list), that line will be
        // treated as the start of a sub-list. What a kludge, huh? This is
        // an aspect of Markdown's syntax that's hard to parse perfectly
        // without resorting to mind-reading. Perhaps the solution is to
        // change the syntax rules such that sub-lists must start with a
        // starting cardinal number; e.g. "1." or "a.".
        $this->list_level++;

        // trim trailing blank lines:
        $list_str = preg_replace("/\n{2,}\\z/", "\n", $list_str);

        $list_str = preg_replace_callback(
            '{
                       (\n)?							# leading line = $1
                       (^[ ]*)							# leading whitespace = $2
                       (' . $marker_any_re . '				# list marker and space = $3
				(?:[ ]+|(?=\n))	# space only required if item is not empty
			)
			((?s:.*?))						# list item text   = $4
			(?:(\n+(?=\n))|\n)				# tailing blank line = $5
			(?= \n* (\z | \2 (' . $marker_any_re . ') (?:[ ]+|(?=\n))))
			}xm',
            function ($matches) {
                $item = $matches [4];
                $leading_line = & $matches [1];
                $leading_space = & $matches [2];
                $marker_space = $matches [3];
                $tailing_blank_line = & $matches [5];

                if ($leading_line || $tailing_blank_line || preg_match('/\n{2,}/', $item)) {
                    // Replace marker with the appropriate whitespace indentation
                    $item = $leading_space . str_repeat(' ', strlen($marker_space)) . $item;
                    $item = $this->runBlockGamut($this->outdent($item) . "\n");
                } else {
                    // Recursion for sub-lists:
                    $item = $this->doLists($this->outdent($item));
                    $item = preg_replace('/\n+$/', '', $item);
                    $item = $this->runSpanGamut($item);
                }

                return "<li>" . $item . "</li>\n";
            },
            $list_str
        );

        $this->list_level--;
        return $list_str;
    }

    var $em_relist = array(
        '' => '(?:(?<!\*)\*(?!\*)|(?<!_)_(?!_))(?=\S|$)(?![\.,:;]\s)',
        '*' => '(?<=\S|^)(?<!\*)\*(?!\*)',
        '_' => '(?<=\S|^)(?<!_)_(?!_)'
    );
    var $strong_relist = array(
        '' => '(?:(?<!\*)\*\*(?!\*)|(?<!_)__(?!_))(?=\S|$)(?![\.,:;]\s)',
        '**' => '(?<=\S|^)(?<!\*)\*\*(?!\*)',
        '__' => '(?<=\S|^)(?<!_)__(?!_)'
    );
    var $em_strong_relist = array(
        '' => '(?:(?<!\*)\*\*\*(?!\*)|(?<!_)___(?!_))(?=\S|$)(?![\.,:;]\s)',
        '***' => '(?<=\S|^)(?<!\*)\*\*\*(?!\*)',
        '___' => '(?<=\S|^)(?<!_)___(?!_)'
    );
    var $em_strong_prepared_relist;

    function prepareItalicsAndBold()
    {
        //
        // Prepare regular expressions for searching emphasis tokens in any
        // context.
        //
        foreach ($this->em_relist as $em => $em_re) {
            foreach ($this->strong_relist as $strong => $strong_re) {
                // Construct list of allowed token expressions.
                $token_relist = array();
                if (isset ($this->em_strong_relist ["$em$strong"])) {
                    $token_relist [] = $this->em_strong_relist ["$em$strong"];
                }
                $token_relist [] = $em_re;
                $token_relist [] = $strong_re;

                // Construct master expression from list.
                $token_re = '{(' . implode('|', $token_relist) . ')}';
                $this->em_strong_prepared_relist ["$em$strong"] = $token_re;
            }
        }
    }

    function doItalicsAndBold($text)
    {
        $token_stack = array(
            ''
        );
        $text_stack = array(
            ''
        );
        $em = '';
        $strong = '';
        $tree_char_em = false;

        while (1) {
            //
            // Get prepared regular expression for seraching emphasis tokens
            // in current context.
            //
            $token_re = $this->em_strong_prepared_relist ["$em$strong"];

            //
            // Each loop iteration search for the next emphasis token.
            // Each token is then passed to handleSpanToken.
            //
            $parts = preg_split($token_re, $text, 2, PREG_SPLIT_DELIM_CAPTURE);
            $text_stack [0] .= $parts [0];
            $token = & $parts [1];
            $text = & $parts [2];

            if (empty ($token)) {
                // Reached end of text span: empty stack without emitting.
                // any more emphasis.
                while ($token_stack [0]) {
                    $text_stack [1] .= array_shift($token_stack);
                    $text_stack [0] .= array_shift($text_stack);
                }
                break;
            }

            $token_len = strlen($token);
            if ($tree_char_em) {
                // Reached closing marker while inside a three-char emphasis.
                if ($token_len == 3) {
                    // Three-char closing marker, close em and strong.
                    array_shift($token_stack);
                    $span = array_shift($text_stack);
                    $span = $this->runSpanGamut($span);
                    $span = "<strong><em>$span</em></strong>";
                    $text_stack [0] .= $this->hashPart($span);
                    $em = '';
                    $strong = '';
                } else {
                    // Other closing marker: close one em or strong and
                    // change current token state to match the other
                    $token_stack [0] = str_repeat($token{0}, 3 - $token_len);
                    $tag = $token_len == 2 ? "strong" : "em";
                    $span = $text_stack [0];
                    $span = $this->runSpanGamut($span);
                    $span = "<$tag>$span</$tag>";
                    $text_stack [0] = $this->hashPart($span);
                    $$tag = ''; // $$tag stands for $em or $strong
                }
                $tree_char_em = false;
            } else {
                if ($token_len == 3) {
                    if ($em) {
                        // Reached closing marker for both em and strong.
                        // Closing strong marker:
                        for ($i = 0; $i < 2; ++$i) {
                            $shifted_token = array_shift($token_stack);
                            $tag = strlen($shifted_token) == 2 ? "strong" : "em";
                            $span = array_shift($text_stack);
                            $span = $this->runSpanGamut($span);
                            $span = "<$tag>$span</$tag>";
                            $text_stack [0] .= $this->hashPart($span);
                            $$tag = ''; // $$tag stands for $em or $strong
                        }
                    } else {
                        // Reached opening three-char emphasis marker. Push on token
                        // stack; will be handled by the special condition above.
                        $em = $token{0};
                        $strong = "$em$em";
                        array_unshift($token_stack, $token);
                        array_unshift($text_stack, '');
                        $tree_char_em = true;
                    }
                } else {
                    if ($token_len == 2) {
                        if ($strong) {
                            // Unwind any dangling emphasis marker:
                            if (strlen($token_stack [0]) == 1) {
                                $text_stack [1] .= array_shift($token_stack);
                                $text_stack [0] .= array_shift($text_stack);
                            }
                            // Closing strong marker:
                            array_shift($token_stack);
                            $span = array_shift($text_stack);
                            $span = $this->runSpanGamut($span);
                            $span = "<strong>$span</strong>";
                            $text_stack [0] .= $this->hashPart($span);
                            $strong = '';
                        } else {
                            array_unshift($token_stack, $token);
                            array_unshift($text_stack, '');
                            $strong = $token;
                        }
                    } else {
                        // Here $token_len == 1
                        if ($em) {
                            if (strlen($token_stack [0]) == 1) {
                                // Closing emphasis marker:
                                array_shift($token_stack);
                                $span = array_shift($text_stack);
                                $span = $this->runSpanGamut($span);
                                $span = "<em>$span</em>";
                                $text_stack [0] .= $this->hashPart($span);
                                $em = '';
                            } else {
                                $text_stack [0] .= $token;
                            }
                        } else {
                            array_unshift($token_stack, $token);
                            array_unshift($text_stack, '');
                            $em = $token;
                        }
                    }
                }
            }
        }
        return $text_stack [0];
    }

    function doBlockQuotes($text)
    {
        $text = preg_replace_callback(
            '/
                                           (								# Wrap whole match in $1
                                           (?>
                                           ^[ ]*>[ ]?			# ">" at the start of a line
                                           .+\n					# rest of the first line
                                           (.+\n)*					# subsequent consecutive lines
                                           \n*						# blanks
                                           )+
                                           )
                                           /xm',
            function ($matches) {
                $bq = $matches [1];
                // trim one level of quoting - trim whitespace-only lines
                $bq = preg_replace('/^[ ]*>[ ]?|^[ ]+$/m', '', $bq);
                $bq = $this->runBlockGamut($bq); // recurse

                $bq = preg_replace('/^/m', "  ", $bq);
                // These leading spaces cause problem with <pre> content,
                // so we need to fix that:
                $bq = preg_replace_callback(
                    '{(\s*<pre>.+?</pre>)}sx',
                    function ($matches) {
                        $pre = $matches [1];
                        $pre = preg_replace('/^  /m', '', $pre);
                        return $pre;
                    },
                    $bq
                );

                return "\n" . $this->hashBlock("<blockquote>\n$bq\n</blockquote>") . "\n\n";
            },
            $text
        );

        return $text;
    }

    function formParagraphs($text)
    {
        //
        // Params:
        // $text - string to process with html <p> tags
        //
        // Strip leading and trailing lines:
        $text = preg_replace('/\A\n+|\n+\z/', '', $text);

        $grafs = preg_split('/\n{2,}/', $text, -1, PREG_SPLIT_NO_EMPTY);

        //
        // Wrap <p> tags and unhashify HTML blocks
        //
        foreach ($grafs as $key => $value) {
            if (!preg_match('/^B\x1A[0-9]+B$/', $value)) {
                // Is a paragraph.
                $value = $this->runSpanGamut($value);
                $value = preg_replace('/^([ ]*)/', "<p>", $value);
                $value .= "</p>";
                $grafs [$key] = $this->unhash($value);
            } else {
                // Is a block.
                // Modify elements of @grafs in-place...
                $graf = $value;
                $block = $this->html_hashes [$graf];
                $graf = $block;
                $grafs [$key] = $graf;
            }
        }

        return implode("\n\n", $grafs);
    }

    function encodeAttribute($text)
    {
        //
        // Encode text for a double-quoted HTML attribute. This function
        // is *not* suitable for attributes enclosed in single quotes.
        //
        $text = $this->encodeAmpsAndAngles($text);
        $text = str_replace('"', '&quot;', $text);
        return $text;
    }

    function encodeAmpsAndAngles($text)
    {

        // Ampersand-encoding based entirely on Nat Irons's Amputator
        // MT plugin: <http://bumppo.net/projects/amputator/>
        $text = preg_replace('/&(?!#?[xX]?(?:[0-9a-fA-F]+|\w+);)/', '&amp;', $text);
        // Encode remaining <'s
        $text = str_replace('<', '&lt;', $text);

        return $text;
    }

    function encodeEmailAddress($addr)
    {
        //
        // Input: an email address, e.g. "foo@example.com"
        //
        // Output: the email address as a mailto link, with each character
        // of the address encoded as either a decimal or hex entity, in
        // the hopes of foiling most address harvesting spam bots. E.g.:
        //
        // <p><a href="&#109;&#x61;&#105;&#x6c;&#116;&#x6f;&#58;&#x66;o&#111;
        // &#x40;&#101;&#x78;&#97;&#x6d;&#112;&#x6c;&#101;&#46;&#x63;&#111;
        // &#x6d;">&#x66;o&#111;&#x40;&#101;&#x78;&#97;&#x6d;&#112;&#x6c;
        // &#101;&#46;&#x63;&#111;&#x6d;</a></p>
        //
        // Based by a filter by Matthew Wickline, posted to BBEdit-Talk.
        // With some optimizations by Milian Wolff.
        //
        $addr = "mailto:" . $addr;
        $chars = preg_split('/(?<!^)(?!$)/', $addr);
        $seed = ( int )abs(crc32($addr) / strlen($addr)); // Deterministic
        // seed.

        foreach ($chars as $key => $char) {
            $ord = ord($char);
            // Ignore non-ascii chars.
            if ($ord < 128) {
                $r = ($seed * (1 + $key)) % 100; // Pseudo-random function.
                // roughly 10% raw, 45% hex, 45%
                // dec
                // '@' *must* be encoded. I insist.
                if ($r > 90 && $char != '@') /* do nothing */
                    ;
                else if ($r < 45)
                    $chars [$key] = '&#x' . dechex($ord) . ';';
                else
                    $chars [$key] = '&#' . $ord . ';';
            }
        }

        $addr = implode('', $chars);
        $text = implode('', array_slice($chars, 7)); // text without
        // `mailto:`
        $addr = "<a href=\"$addr\">$text</a>";

        return $addr;
    }

    function parseSpan($str)
    {
        //
        // Take the string $str and parse it into tokens, hashing embeded HTML,
        // escaped characters and handling code spans.
        //
        $output = '';

        $span_re = '{
				(
					\\\\[' . preg_quote('\`*_{}[]()>#+-.!') . ']
					|
					(?<![`\\\\])
					`+						# code span marker
					|
					<!-- .*? -->		# comment
					|
					<\?.*?\?> | <%.*?%> # processing instruction
					|
					<[/!$]?[-a-zA-Z0-9:_]+	# regular tags
					(?>
						\s
						(?>[^"\'>]+|"[^"]*"|\'[^\']*\')*
					)?
					>
				)
			}xs';

        while (1) {
            //
            // Each loop iteration seach for either the next tag, the next
            // openning code span marker, or the next escaped character.
            // Each token is then passed to handleSpanToken.
            //
            $parts = preg_split($span_re, $str, 2, PREG_SPLIT_DELIM_CAPTURE);

            // Create token from text preceding tag.
            if ($parts [0] != "") {
                $output .= $parts [0];
            }

            // Check if we reach the end.
            if (isset ($parts [1])) {
                $output .= $this->handleSpanToken($parts [1], $parts [2]);
                $str = $parts [2];
            } else {
                break;
            }
        }

        return $output;
    }

    function handleSpanToken($token, &$str)
    {
        //
        // Handle $token provided by parseSpan by determining its nature and
        // returning the corresponding value that should replace it.
        //
        switch ($token{0}) {
            case "\\" :
                return $this->hashPart("&#" . ord($token{1}) . ";");
            case "`" :
                // Search for end marker in remaining text.
                if (preg_match('/^(.*?[^`])' . preg_quote($token) . '(?!`)(.*)$/sm', $str, $matches)) {
                    $str = $matches [2];

                    //
                    // Create a code span markup for $code. Called from
                    // handleSpanToken.
                    //
                    $codespan = htmlspecialchars(trim($matches [1]), ENT_NOQUOTES);
                    $codespan = $this->hashPart("<code>$codespan</code>");
                    return $this->hashPart($codespan);
                }
                return $token; // return as text since no ending marker found.
            default :
                return $this->hashPart($token);
        }
    }

    function outdent($text)
    {
        //
        // Remove one level of line-leading tabs or spaces
        //
        return preg_replace('/^(\t|[ ]{1,2})/m', '', $text);
    }

    function detab($text)
    {
        //
        // Replace tabs with the appropriate amount of space.
        //
        // For each line we separate the line in blocks delemited by
        // tab characters. Then we reconstruct every line by adding the
        // appropriate number of space between each blocks.
        $text = preg_replace_callback(
            '/^.*\t.*$/m',
            function ($matches) {
                $line = $matches [0];

                // Split in blocks.
                $blocks = explode("\t", $line);
                // Add each blocks to the line.
                $line = $blocks [0];
                unset ($blocks [0]); // Do not add first block twice.
                foreach ($blocks as $block) {
                    // Calculate amount of space, insert spaces, insert block.
                    $amount = 2 - mb_strlen($line, 'UTF-8') % 2;
                    $line .= str_repeat(" ", $amount) . $block;
                }
                return $line;
            },
            $text
        );

        return $text;
    }

    function unhash($text)
    {
        //
        // Swap back in all the tags hashed by _HashHTMLBlocks.
        //
        return preg_replace_callback(
            '/(.)\x1A[0-9]+\1/',
            function ($matches) {
                return $this->html_hashes [$matches [0]];
            },
            $text
        );
    }
}
	