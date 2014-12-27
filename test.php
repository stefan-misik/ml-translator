<?php


include_once("ml-code.php");







$text = "

Empty List:
[ul]
[/ul]

Unordered list:
[ul]
[*] [b]Item 1[/b]
[*] [u]Item 2[/u]
[*] [i]Item 3[/i]
[/ul]

Ordered list:
[ol]
[*] Item 1
[*] Item 2
[*] Item 3
[/ol]

[url='http://google.com']Link do googleu[/url]

[p]
par1
[/p]
[p]
par2
[/p]

[p][b][/b][/p]
";

//echo $test;

$t = microtime(true);

echo MLcode::translate($text);
echo "<br><br> <textarea rows=\"10\" cols=\"80\">" . MLcode::translate($text, true) . "</textarea>";
echo microtime(true) - $t;
echo "<br><br> <textarea rows=\"10\" cols=\"80\">" . $text . "</textarea>";


//echo MLcode::translate($text, true);

/*phpinfo();*/




