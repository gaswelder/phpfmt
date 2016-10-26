# phpfmt

<blockquote>“... I corrected them. I corrected them<br>
most harshly. And when my wife tried to<br>
stop me from doing my duty, I corrected<br>
her.”</blockquote>
— Delbert Grady from Stephen King's <cite>The Shining</cite>.

phpfmt formats PHP code into a style somewhat similar to the K&R style.

The usage is:

	phpfmt [-rp] [paths...]

If no paths are given, acts as a `stdin`-`stdout` filter. Otherwise
processes each file from the `paths` list, formatting its contents
in-place.

The `r` flag allows recursing into directories. Without it paths from
the command line that point to directories will be ignored.

The `p` flag tells phpfmt to print paths of changed files.

phpfmt doesn't take HTML code into account, so it's not suited for 
dealing with web templates.


## Disclaimer

Over the years I've developed tolerance to many styles out there and
stopped getting agitated about them (which I recommend to
everyone else) to the point where I even started shifting randomly
between styles myself (which I don't recommend to everyone else). I
wrote phpfmt to try out some ideas, but now that it's here, why
not use it.

The script uses the native `token_get_all` function. At first I thought
having tokens is enough for formatting, and it would be simple enough.
It turned out, having completely parsed code is better, but it's still
possible to get reasonable results with just the tokens.
